<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->boolean('approval_mail_enabled')->default(true);
            $table->string('approval_mail_subject')->nullable();
            $table->text('approval_mail_body')->nullable();
            $table->string('approval_mail_button_label', 80)->nullable();
            $table->string('approval_mail_button_url', 500)->nullable();

            $table->boolean('rejection_mail_enabled')->default(true);
            $table->string('rejection_mail_subject')->nullable();
            $table->text('rejection_mail_body')->nullable();
            $table->string('rejection_mail_button_label', 80)->nullable();
            $table->string('rejection_mail_button_url', 500)->nullable();

            $table->boolean('whatsapp_group_enabled')->default(false);
            $table->string('whatsapp_group_url', 500)->nullable();
            $table->boolean('whatsapp_group_in_approval')->default(true);
            $table->boolean('whatsapp_group_in_rejection')->default(true);
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('decision_mail_status', 20)->nullable();
            $table->string('decision_mail_state', 20)->nullable();
            $table->timestamp('decision_mail_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn(['decision_mail_status', 'decision_mail_state', 'decision_mail_at']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn([
                'approval_mail_enabled', 'approval_mail_subject', 'approval_mail_body', 'approval_mail_button_label', 'approval_mail_button_url',
                'rejection_mail_enabled', 'rejection_mail_subject', 'rejection_mail_body', 'rejection_mail_button_label', 'rejection_mail_button_url',
                'whatsapp_group_enabled', 'whatsapp_group_url', 'whatsapp_group_in_approval', 'whatsapp_group_in_rejection',
            ]);
        });
    }
};
