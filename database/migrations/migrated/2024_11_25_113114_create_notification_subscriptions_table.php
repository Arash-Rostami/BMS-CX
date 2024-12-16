<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_subscriptions', function (Blueprint $table) {
            $table->increments('id')->unsigned()->index();
            $table->morphs('notifiable');
            $table->boolean('email')->default(false);
            $table->boolean('in_app')->default(true);
            $table->boolean('sms')->default(false);
            $table->boolean('notify_create')->default(false);
            $table->boolean('notify_update')->default(false);
            $table->boolean('notify_delete')->default(false);
            $table->integer('user_id')->unsigned()->index();
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();

            $table->unique(['user_id', 'notifiable_type', 'notifiable_id'], 'user_notifiable_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_subscriptions');
    }
};
