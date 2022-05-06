<?php

namespace HulkApps\AppManager\app\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanController extends Controller
{
    public function index() {

        $features = config('plan_features');

        return response()->json(['features' => $features]);
    }

    public function plans(Request $request) {

        $activePlanId = $shopify_plan = $plan = null;
        $shopTableName = config('app-manager.shop_table_name', 'users');
        $storeFieldName = config('app-manager.field_names.name', 'name');
        $planFieldName = config('app-manager.field_names.plan_id', 'plan_id');
        $shopifyPlanFieldName = config('app-manager.field_names.shopify_plan', 'shopify_plan');

        $plans = \AppManager::getPlans();

        if ($request->has('shop_domain')) {
            $shopDomain = $request->get('shop_domain');
            $userData = DB::table($shopTableName)->where($storeFieldName, $shopDomain)->get();
            $shopify_plan = collect($userData)->pluck($shopifyPlanFieldName)->first();
            $activePlanId = collect($userData)->pluck($planFieldName)->first();
            $plan = collect($plans)->where('id', $activePlanId)->first();
        }

        $defaultPlanId = collect($plans)->where('interval', 'EVERY_30_DAYS')->sortByDesc('price')->pluck('id')->first();

        $response = [
            'plans' => $plans,
            'shopify_plan' => $shopify_plan,
            'plan' => $plan,
            'default_plan_id' => $defaultPlanId
        ];

        return response()->json($response);
    }

    public function users(Request $request) {

        $tableName = config('app-manager.shop_table_name', 'users');
        $shopify_fields = config('app-manager.field_names');
        $users = DB::table($tableName)->paginate(2);
        $users->getCollection()->transform(function ($user) use ($shopify_fields) {
            foreach ($shopify_fields as $key => $shopify_field) {
                if ($key !== $shopify_field) {
                    $user->{$key} = $user->{$shopify_field};
                }
            }
            return $user;
        });

        /*$users->map(function ($user) use ($shopify_fields) {
            foreach ($shopify_fields as $key => $shopify_field) {
                if ($key !== $shopify_field) {
                    $user->{$key} = $user->{$shopify_field};
                }
            }
            return $usersData;
        });*/

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
}