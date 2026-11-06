<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('trade_name')->nullable()->default('Nyleo');
            $table->string('legal_form')->nullable();
            $table->string('siret')->nullable();
            $table->string('rcs')->nullable();
            $table->string('ape_code')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable()->default('https://nyleo.fr');
            $table->string('logo_path')->nullable();
            $table->string('iban')->nullable();
            $table->string('bic')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('cheque_order')->nullable();
            $table->string('cheque_address')->nullable();
            $table->boolean('vat_enabled')->default(false);
            $table->decimal('default_vat_rate', 5, 2)->default(20);
            $table->string('vat_exemption_mention')->nullable()->default('TVA non applicable, art. 293 B du CGI');
            $table->unsignedSmallInteger('quote_validity_days')->default(30);
            $table->unsignedSmallInteger('payment_terms_days')->default(15);
            $table->text('payment_terms_text')->nullable();
            $table->text('late_penalty_text')->nullable();
            $table->text('quote_footer')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->string('quote_prefix')->default('D');
            $table->string('invoice_prefix')->default('F');
            $table->string('credit_note_prefix')->default('AV');
            $table->unsignedInteger('quote_next_number')->default(1);
            $table->unsignedInteger('invoice_next_number')->default(1);
            $table->unsignedInteger('credit_note_next_number')->default(1);
            $table->unsignedSmallInteger('reminder_first_days')->default(7);
            $table->unsignedSmallInteger('reminder_interval_days')->default(7);
            $table->unsignedSmallInteger('reminder_max')->default(3);
            $table->unsignedSmallInteger('quote_reminder_days')->default(7);
            $table->boolean('auto_reminders')->default(true);
            $table->string('email_signature')->nullable();
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('particulier'); // particulier | professionnel
            $table->string('civility')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name');
            $table->string('company_name')->nullable();
            $table->string('siret')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->text('notes')->nullable();
            $table->string('source')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('catalog_items', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('unit')->default('forfait');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('dossiers', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('procedure'); // DP | PC | PC_ARCHI | PA | AUTRE
            $table->string('nature')->nullable();
            $table->text('description')->nullable();
            $table->string('site_address')->nullable();
            $table->string('site_postal_code')->nullable();
            $table->string('site_city')->nullable();
            $table->string('department')->nullable();
            $table->string('cadastre_section')->nullable();
            $table->string('cadastre_number')->nullable();
            $table->decimal('land_area', 10, 2)->nullable();
            $table->boolean('abf')->default(false);
            $table->boolean('is_individual_house')->default(true);
            $table->string('status')->default('nouveau');
            $table->date('deposit_date')->nullable();
            $table->string('deposit_number')->nullable();
            $table->string('deposit_channel')->nullable();
            $table->date('incomplete_notified_at')->nullable();
            $table->date('incomplete_deadline')->nullable();
            $table->date('decision_due_date')->nullable();
            $table->date('decision_date')->nullable();
            $table->text('client_message')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dossier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('brouillon');
            $table->string('subject')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('valid_until')->nullable();
            $table->decimal('total_ht', 10, 2)->default(0);
            $table->decimal('total_vat', 10, 2)->default(0);
            $table->decimal('total_ttc', 10, 2)->default(0);
            $table->decimal('deposit_percent', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('client_snapshot')->nullable();
            $table->json('company_snapshot')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signer_name')->nullable();
            $table->string('signer_ip')->nullable();
            $table->text('signer_user_agent')->nullable();
            $table->string('signature_hash')->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->timestamp('refused_at')->nullable();
            $table->text('refusal_reason')->nullable();
            $table->unsignedSmallInteger('reminder_count')->default(0);
            $table->timestamp('last_reminder_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quote_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('total_ht', 10, 2)->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->string('type')->default('facture'); // facture | acompte | avoir
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dossier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('status')->default('brouillon');
            $table->string('subject')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('total_ht', 10, 2)->default(0);
            $table->decimal('total_vat', 10, 2)->default(0);
            $table->decimal('total_ttc', 10, 2)->default(0);
            $table->decimal('deposits_deducted', 10, 2)->default(0);
            $table->decimal('amount_due', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('client_snapshot')->nullable();
            $table->json('company_snapshot')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->unsignedSmallInteger('reminder_count')->default(0);
            $table->timestamp('last_reminder_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->text('description')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('total_ht', 10, 2)->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method')->default('virement'); // virement | cheque | especes | carte
            $table->date('paid_at');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained()->cascadeOnDelete();
            $table->string('category')->default('autre');
            $table->string('name');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedSmallInteger('version')->nullable();
            $table->string('uploaded_by')->default('admin'); // admin | client
            $table->boolean('visible_to_client')->default(true);
            $table->boolean('requires_validation')->default(false);
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('change_requested_at')->nullable();
            $table->text('client_feedback')->nullable();
            $table->timestamps();
        });

        Schema::create('dossier_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('message');
            $table->boolean('visible_to_client')->default(false);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['dossier_events', 'documents', 'payments', 'invoice_lines', 'invoices', 'quote_lines', 'quotes', 'dossiers', 'catalog_items', 'clients', 'company_settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
