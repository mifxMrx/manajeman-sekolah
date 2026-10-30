<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->time('check_in_time')->nullable()->after('status');
        });
    }

    public function down()
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->dropColumn('check_in_time');
        });
    }
};
