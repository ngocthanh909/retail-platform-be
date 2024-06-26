<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                        if (isset($dateRangeDt[0])) {
                            $start = Carbon::make($dateRangeDt[0])->format('Y-m-d');
                        }
                        if (isset($dateRangeDt[1])) {
                            $end = Carbon::make($dateRangeDt[1])->format('Y-m-d');
                        }
                        break;
                }
            }

            $employeeId = $request->employee_id;
            $customerId = $request->customer_id;
            $employee = User::findOrFail($employeeId);

            $dateCondition = "and DATE(o.created_at) BETWEEN '$start' and '$end'";
            $customerCondition = $customerId ? "and o.customer_id = $customerId" : '';
            DB::enableQueryLog();
            $customerOrderByTime = DB::select("
            SELECT DISTINCT
                o.customer_id AS customer_id,
                c.customer_name,
                c.phone,
                CONCAT(c.address, ', ', c.district, ', ', c.province) AS full_address
            FROM
                orders o
            JOIN
                customers c ON o.customer_id = c.id
            WHERE
                o.status = 3
                AND o.responsible_staff = $employeeId $dateCondition $customerCondition");

            foreach ($customerOrderByTime as $customerCommission) {
                $customerId = $customerCommission->customer_id;
                $detailCommissionInfo = DB::select("select count(o.id) order_count, sum(o.total_commission) total_commission from orders o where o.responsible_staff = $employeeId and  o.status = 3 and o.customer_id = $customerId $dateCondition group by customer_id limit 1");
                $customerCommission->order_count = $detailCommissionInfo[0]?->order_count ?? 0;
                $customerCommission->order_commission = $detailCommissionInfo[0]?->total_commission ?? 0;
                $customerCommission->employee_name = $employee->name ?? '';
                $customerCommission->employee_id = $employee->id ?? '';
            }
            $sum = array_sum(data_get($customerOrderByTime, '*.order_commission'));
            return $this->success(['list' => $customerOrderByTime, 'total' => $sum]);
        } catch (\Throwable $e) {
            Log::error($e);
            if ($e instanceof ModelNotFoundException) {
                return $this->failure('Lỗi lấy báo cáo hoa hồng', 'Không tìm thấy nhân viên này');
            }
            return $this->failure('Lỗi lấy báo cáo hoa hồng', $e->getMessage());
        }
    }

    function getEmployeeStoreCommission(Request $request)
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
                        $end = now()->format('Y-m-d');
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
                        if (isset($dateRangeDt[0])) {
                            $start = Carbon::make($dateRangeDt[0])->format('Y-m-d');
                        }
                        if (isset($dateRangeDt[1])) {
                            $end = Carbon::make($dateRangeDt[1])->format('Y-m-d');
                        }
                        break;
                }
            }

            $employeeId = $request->employee_id;
            $customerId = $request->customer_id;
            $orders = Order::where('responsible_staff', $employeeId)->where('customer_id', $customerId)->where('total_commission', '>', 0)->where('status', 3)->whereRaw("DATE(created_at) BETWEEN '$start' and '$end'")->get();
            foreach($orders as $order){
                $orderId = $order->id;
                $order->commissions = DB::select("
                SELECT
                    od.category_id,
                    SUM(od.category_commission_amount) AS sum,
                    od.category_name,
                    od.category_commission_rate,
                    sum(qty) as qty
                FROM
                    order_details od
                WHERE
                    od.order_id = $orderId
                GROUP BY
                    od.category_id, od.category_name, od.category_commission_rate;");
            }
            $sum = array_sum(data_get($orders, '*.total_commission'));
            return $this->success(['list' => $orders, 'total' => $sum]);
        } catch (\Throwable $e) {
            Log::error($e);
            if ($e instanceof ModelNotFoundException) {
                return $this->failure('Lỗi lấy báo cáo hoa hồng theo khách hàng', 'Không tìm thấy nhân viên này');
            }
            return $this->failure('Lỗi lấy báo cáo hoa hồng theo khách hàng', $e->getMessage());
        }
    }
}
