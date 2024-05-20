<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommissionManagementController extends Controller
{
    use ApiResponseTrait;

    function getEmployeesCommission(Request $request)
    {
        try {
            $data = $request->all();
            $dateRange = $data['range'] ?? false;
            $start = null;
            $end = null;
            if ($dateRange) {
                switch ($dateRange) {
                    case 'today':
                        $start = now()->format('Y-m-d');
                        break;
                    case 'this_week':
                        $start = now()->startOfWeek()->format('Y-m-d');
                        $end = now()->endOfWeek()->format('Y-m-d');
                        break;
                    case 'last_week':
                        $start = now()->subWeek()->startOfWeek()->format('Y-m-d');
                        $end = now()->subWeek()->endOfWeek()->format('Y-m-d');
                        break;
                    case 'this_month':
                        $start = now()->startOfMonth()->format('Y-m-d');
                        $end = now()->endOfMonth()->format('Y-m-d');
                        break;
                    case 'last_month':
                        $start = now()->subMonth()->startOfMonth()->format('Y-m-d');
                        $end = now()->subMonth()->endOfMonth()->format('Y-m-d');
                        break;
                    case 'this_year':
                        $start = now()->startOfYear()->format('Y-m-d');
                        $end = now()->endOfYear()->format('Y-m-d');
                        break;
                    case 'last_year':
                        $start = now()->subYear()->startOfYear()->format('Y-m-d');
                        $end = now()->subYear()->endOfYear()->format('Y-m-d');
                        break;
                    default:
                        $dateRangeDt = explode(',', $dateRange);
                        if(isset($dateRangeDt[0])){
                            $start = Carbon::make($dateRangeDt[0])->format('Y-m-d');
                        }
                        if(isset($dateRangeDt[1])){
                            $end = Carbon::make($dateRangeDt[1])->format('Y-m-d');
                        }
                        break;
                }
            }

            $employeeId = $request->employee_id;
            $employee = User::find($employeeId);
            $dateCondition = "and DATE(o.created_at) > '$start' and DATE(o.created_at) <= '$end'";
            $customerOrderByTime = DB::select("select distinct customer_id, customer_name, phone,  concat(address, ',', district, ',', province) as full_address from orders o where o.status = 3 and o.responsible_staff = $employeeId $dateCondition");
            foreach ($customerOrderByTime as $customerCommission) {
                $customerId = $customerCommission->customer_id;
                $detailCommissionInfo = DB::select("select count(o.id) order_count, sum(o.total_commission) total_commission from orders o where o.responsible_staff = $employeeId and  o.status = 3 and o.customer_id = $customerId $dateCondition group by customer_id limit 1");
                $customerCommission->order_count = $detailCommissionInfo[0]?->order_count ?? 0;
                $customerCommission->order_commission = $detailCommissionInfo[0]?->total_commission ?? 0;
                $customerCommission->employee_name = $employee->name ?? '';
                $customerCommission->employee_id = $employee->id ?? '';
            }
            return $this->success($customerOrderByTime);
        } catch (\Throwable $e) {
            return $this->failure('Lỗi lấy báo cáo hoa hồng', $e->getMessage());
        }
    }
}