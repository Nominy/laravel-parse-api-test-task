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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->date('last_change_date')->nullable()->index();
            $table->string('supplier_article', 100)->nullable()->index();
            $table->string('tech_size', 100)->nullable();
            $table->bigInteger('barcode')->index();
            $table->integer('quantity')->default(0);
            $table->boolean('is_supply')->nullable();
            $table->boolean('is_realization')->nullable();
            $table->integer('quantity_full')->nullable();
            $table->string('warehouse_name')->nullable()->index();
            $table->integer('in_way_to_client')->nullable();
            $table->integer('in_way_from_client')->nullable();
            $table->bigInteger('nm_id')->nullable()->index();
            $table->string('subject', 100)->nullable();
            $table->string('category', 100)->nullable()->index();
            $table->string('brand', 100)->nullable()->index();
            $table->bigInteger('sc_code')->nullable()->index();
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('discount')->nullable();
            $table->timestamps();

        });

        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('income_id')->index();
            $table->string('number')->nullable();
            $table->date('date')->index();
            $table->date('last_change_date')->nullable()->index();
            $table->string('supplier_article', 100)->nullable()->index();
            $table->string('tech_size', 100)->nullable();
            $table->bigInteger('barcode')->index();
            $table->integer('quantity')->default(0);
            $table->decimal('total_price', 12, 2)->nullable();
            $table->date('date_close')->nullable();
            $table->string('warehouse_name')->nullable()->index();
            $table->bigInteger('nm_id')->nullable()->index();
            $table->timestamps();

            $table->index(['date', 'warehouse_name']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('g_number', 100)->index();
            $table->date('date')->index();
            $table->date('last_change_date')->nullable()->index();
            $table->string('supplier_article', 100)->nullable()->index();
            $table->string('tech_size', 100)->nullable();
            $table->bigInteger('barcode')->index();
            $table->integer('quantity')->default(0);
            $table->decimal('total_price', 12, 2)->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->boolean('is_supply')->nullable();
            $table->boolean('is_realization')->nullable();
            $table->decimal('promo_code_discount', 10, 2)->nullable();
            $table->string('warehouse_name')->nullable()->index();
            $table->string('country_name', 100)->nullable();
            $table->string('oblast_okrug_name')->nullable();
            $table->string('region_name')->nullable();
            $table->bigInteger('income_id')->nullable()->index();
            $table->string('sale_id', 50);
            $table->bigInteger('odid')->nullable();
            $table->decimal('spp', 10, 2)->nullable();
            $table->decimal('for_pay', 12, 2)->nullable();
            $table->decimal('finished_price', 12, 2)->nullable();
            $table->decimal('price_with_disc', 12, 2)->nullable();
            $table->bigInteger('nm_id')->nullable()->index();
            $table->string('subject', 100)->nullable();
            $table->string('category', 100)->nullable()->index();
            $table->string('brand', 100)->nullable()->index();
            $table->boolean('is_storno')->nullable();
            $table->timestamps();

            $table->index(['date', 'warehouse_name']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('g_number', 100)->index();
            $table->timestamp('date')->index();
            $table->date('last_change_date')->nullable()->index();
            $table->string('supplier_article', 100)->nullable()->index();
            $table->string('tech_size', 100)->nullable();
            $table->bigInteger('barcode')->index();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->integer('discount_percent')->nullable();
            $table->string('warehouse_name')->nullable()->index();
            $table->string('oblast')->nullable()->index();
            $table->bigInteger('income_id')->nullable()->index();
            $table->string('odid', 50)->nullable();
            $table->bigInteger('nm_id')->nullable()->index();
            $table->string('subject', 100)->nullable();
            $table->string('category', 100)->nullable()->index();
            $table->string('brand', 100)->nullable()->index();
            $table->boolean('is_cancel')->default(false)->index();
            $table->date('cancel_dt')->nullable();
            $table->timestamps();

            $table->index(['date', 'warehouse_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('stocks');
    }
};
