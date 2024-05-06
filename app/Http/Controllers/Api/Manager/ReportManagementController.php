<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportManagementController extends Controller
{
    use ApiResponseTrait;
    public function report(Request $request)
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
                    default:
                        break;
                }
            }
            $store = $data['customer'] ?? false;


            $condition = "";

            if ($start && !$end) {
                $condition = "where DATE(o.created_at) = DATE('$start')";
            } elseif ($start && $end) {
                $condition = "where DATE(o.created_at) >= DATE('$start') and DATE(o.created_at) <= DATE('$end')";
            }
            if (!empty($store)) {
                $condition .= empty($condition) ? "where customer_id = $store" : "and customer_id = $store";
            }

            $employeeCondition = $condition;
            $employee = $request->employee ?? 0;
            if (!empty($employee)) {
                $employeeCondition .= empty($employeeCondition) ? "where responsible_staff = '$employee' or u.name like '%$employee%'" : "and responsible_staff = '$employee' or u.name like '%$employee%'";
            }

            $sqlRevenueByTime = "select DATE(created_at) as date, count(id) as number_of_order, sum(total) as total, count(case when status = 0 then id END) as cancelled from orders o $condition group by DATE(created_at)";
            $sqlRevenueByEmployee = "select sum(o.total) as total, u.name, u.id, count(o.id) as number_of_order  from orders o inner join users u on o.responsible_staff = u.id $employeeCondition group by responsible_staff;";

            $revenueByTime = DB::select($sqlRevenueByTime);
            $sqlRevenueByEmployee = DB::select($sqlRevenueByEmployee);

            $overallReportSql = "
            select
                count(o.id) as number_of_order,
                sum(o.total) as order_value,
                case
                    when o.status = 0 then 'active'
                    WHEN o.status  <> 0 THEN 'cancelled'
                end as order_status
            from orders o $condition
            group by order_status;";

            $overallReport = DB::select($overallReportSql);

            return $this->success(["revenue_by_time" => $revenueByTime, 'revenue_by_employee' => $sqlRevenueByEmployee, 'overall' => $overallReport]);
        } catch (\Throwable $e) {
            Log::error($e);
            return $this->failure("Lỗi lấy báo cáo", $e->getMessage());
        }
    }
}
