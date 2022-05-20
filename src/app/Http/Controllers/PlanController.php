<?php

namespace HulkApps\AppManager\app\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index() {

        $features = config('plan_features');

        return response()->json(['features' => $features]);
    }

    public function plans(Request $request) {

        $shopTableName = config('app-manager.shop_table_name', 'users');
        $storeFieldName = config('app-manager.field_names.name', 'name');
        $planFieldName = config('app-manager.field_names.plan_id', 'plan_id');
        $shopifyPlanFieldName = config('app-manager.field_names.shopify_plan', 'shopify_plan');

        $cacheKey = $request->has('shop_domain') ? 'app-manager.plans-'.$request->get('shop_domain') : 'app-manager.all-plans';

        $response = Cache::rememberForever($cacheKey, function () use ($request, $shopTableName, $storeFieldName, $planFieldName, $shopifyPlanFieldName, $cacheKey) {
            $shopify_plan = $plan = null;
            $plans = \AppManager::getPlans();

            if ($request->has('shop_domain')) {
                $shopDomain = $request->get('shop_domain');
                $userData = DB::table($shopTableName)->where($storeFieldName, $shopDomain)->get();
                $shopify_plan = collect($userData)->pluck($shopifyPlanFieldName)->first();
                $activePlanId = collect($userData)->pluck($planFieldName)->first();
                $plan = collect($plans)->where('id', $activePlanId)->first();
            }

            $defaultPlanId = collect($plans)->where('interval', 'EVERY_30_DAYS')->sortByDesc('price')->pluck('id')->first();

            return [
                'plans' => $plans,
                'shopify_plan' => $shopify_plan,
                'plan' => $plan,
                'default_plan_id' => $defaultPlanId
            ];
        });

        return response()->json($response);
    }

    public function users(Request $request) {
        $search = $request->get('search') ?? null;
        $tableName = config('app-manager.shop_table_name', 'users');
        $shopify_fields = config('app-manager.field_names');
        $users = DB::table($tableName)->when($search, function ($q) use ($shopify_fields, $search) {
            return $q->where(($shopify_fields['name'] ?? 'name'), 'like', '%'.$search.'%')
                ->orWhere(($shopify_fields['shopify_email'] ?? 'shopify_email'), 'like', '%'.$search.'%');
        })->paginate(10);
        $users->getCollection()->transform(function ($user) use ($shopify_fields) {
            foreach ($shopify_fields as $key => $shopify_field) {
                if ($key !== $shopify_field) {
                    $user->{$key} = $user->{$shopify_field};
                }
            }
            return $user;
        });

        return response()->json($users, 200);
    }

    public function activeWithoutPlan(Request $request) {
        $tableName = config('app-manager.shop_table_name', 'users');
        $shopify_fields = config('app-manager.field_names');
        $shop_domain = $request->get('shop_domain');
        if (!$shop_domain) {
            return response()->json(['message' => 'shop domain is required'], 422);
        }

        $user = DB::table($tableName)->where($shopify_fields['name'], $request->get('shop_domain'))
            ->limit(1)->update([
                'plan_id' => $request->get('plan_id'),
                'trial_activated_at' => Carbon::now()
            ]);
        if ($user) {
            return response()->json(['status' => true]);
        }
        return response()->json(['status' => false], 422);
    }

    public function burstCache(Request $request) {
        Cache::forget('app-manager');

        return true;
    }

    public function failSafeBackup(Request $request) {
//		shell_exec('php ../artisan app-manager:init-db');
        $this->initializeFailsafeDB();
        $data = $request->all();

        $marketingBanners = [
            'marketing_banners' => json_encode(head($data['app_structures']))
        ];
        DB::connection('app-manager-sqlite')->table('marketing_banners')->insert($marketingBanners);

        $plans = $this->filterData($data['plans']);
        foreach ($plans as $index => $plan) {
            $plans[$index] = $this->serializeData($plan);
            $plans[$index]['feature_plan'] = $plans[$index]['features'];
            unset($plans[$index]['features']);
        }
        DB::connection('app-manager-sqlite')->table('plans')->insert($plans);

        $charges = $this->filterData($data['charges']);
        DB::connection('app-manager-sqlite')->table('charges')->insert($charges);

        $discount_plans = $this->filterData($data['discount_plans']);
        DB::connection('app-manager-sqlite')->table('discount_plan')->insert($discount_plans);

        $extend_trials = $this->filterData($data['extend_trials']);
        DB::connection('app-manager-sqlite')->table('trial_extension')->insert($extend_trials);
    }

    public function serializeData ($data) {
        foreach ($data as $index => $datum) {
            if (gettype($datum) == 'array') {
                $data[$index] = json_encode($datum);
            }
        }
        return $data;
    }

    public function filterData($data) {
        $data = collect($data)->map(function ($value, $key) {
            return collect($value)->forget('app_id')->toArray();
        })->toArray();
        return $data;
    }

    protected function initializeFailsafeDB() {
        $db_path = storage_path('app-manager/database.sqlite');
        if (File::exists($db_path)) {
            File::delete($db_path);
        }

        File::put($db_path,'');

        Artisan::call('migrate', ['--database' => 'app-manager-sqlite', '--path' => "vendor/hulkapps/appmanager/migrations"]);
    }
}
