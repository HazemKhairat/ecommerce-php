<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'category_id', 'base_price'], 'idx_products_status_category_price');

            $table->index(['status', 'brand_id', 'base_price'], 'idx_products_status_brand_price');

            $table->index(['status', 'created_at'], 'idx_products_status_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_status_category_price');
            $table->dropIndex('idx_products_status_brand_price');
            $table->dropIndex('idx_products_status_created_at');
        });
    }
};