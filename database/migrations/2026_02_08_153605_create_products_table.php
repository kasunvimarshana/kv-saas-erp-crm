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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('product_type', ['goods', 'service', 'consumable'])->default('goods');
            $table->foreignId('category_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('unit_of_measure_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->string('barcode')->nullable()->unique();
            $table->string('sku')->nullable()->unique();
            $table->boolean('track_inventory')->default(true);
            $table->decimal('reorder_level', 12, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'product_type', 'status']);
            $table->index(['code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
