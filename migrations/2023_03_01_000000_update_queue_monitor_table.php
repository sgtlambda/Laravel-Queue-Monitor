<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use romanzipp\QueueMonitor\Enums\MonitorStatus;
use romanzipp\QueueMonitor\Models\Monitor;

class UpdateQueueMonitorTable extends Migration
{
    public function up()
    {
        Schema::table(config('queue-monitor.table'), function (Blueprint $table) {
            $table->unsignedInteger('status')->default(MonitorStatus::RUNNING)->after('queue');
        });

        $this->upgradeColumns();

        Schema::table(config('queue-monitor.table'), function (Blueprint $table) {
            $table->dropColumn(['failed', 'time_elapsed']);
        });
    }

    public function upgradeColumns()
    {
        DB::table(config('queue-monitor.table'))->update([
            'status' => DB::raw('CASE ' .
                'WHEN finished_at IS NOT NULL THEN ' . MonitorStatus::SUCCEEDED . ' ' .
                'WHEN failed = 1 THEN ' . MonitorStatus::FAILED . ' ' .
                'ELSE ' . MonitorStatus::RUNNING . ' ' .
                'END'),
        ]);
    }

    public function down()
    {
        Schema::table(config('queue-monitor.table'), function (Blueprint $table) {
            $table->dropColumn('status');

            $table->float('time_elapsed', 12, 6)->nullable()->index();
            $table->boolean('failed')->default(false)->index();
        });
    }
}
