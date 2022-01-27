<?php
class AdminController extends Controller {
    
    public function index() {
        $stats   = $this->getStatsData();
        $dates   = $this->getChartDates(14);

        $this->set("chart_cols", array_keys($dates['chart']));
        $this->set("thread_data", Topics::getTopicsChartData($dates));
        $this->set("reply_data", Replies::getRepliesChartData($dates));
        $this->set("stats", $stats);
        return true;
    }

    public function getChartDates($dayLimit = 14, $format = "y.m.d") {
        $start = strtotime(date("Y-m-d 00:00:00")." -$dayLimit days");
        $end   = strtotime(date("Y-m-d 23:59:59"));

        $data = [
            'start'  => $start,
            'format' => $format,
            'chart'  => [],
        ];

        while($start < $end) {
            $start += 86400; // increment by 1 day until we reach today
            $date = date($format, $start);
            $data['chart'][$date] = 0;
            $data['chart'][$date] = 0;
        }

        return $data;
    }

    public function getStatsData() {
        $startTime = strtotime(date("Y-m-01 00:00:00"));

        $data = [
            'users' => [
                'total'   => Users::count(),
                'monthly' => Users::where('created', '>=', $startTime)->count()
            ],
            'topics' => [
                'total'   => Topics::count(),
                'monthly' => Topics::where('started', '>=', $startTime)->count()
            ],
            'replies' => [
                'total'   => Replies::count(),
                'monthly' => Replies::where('posted', '>=', $startTime)->count()
            ]
        ];

        return $data;
    }
}