<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rest_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained()->onDelete('cascade');
            $table->dateTime('rest_in_time')->nullable();
            $table->dateTime('rest_out_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rests_records');
    }
}
