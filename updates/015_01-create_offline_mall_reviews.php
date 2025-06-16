<?php

namespace WebBook\Mall\Updates;

use October\Rain\Database\Updates\Migration;
use Schema;

class CreateWebBookMallReviews extends Migration
{
    public function up()
    {
        Schema::create('webbook_mall_review_categories', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
        Schema::create('webbook_mall_category_review_category', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('category_id');
            $table->integer('review_category_id');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['category_id', 'review_category_id'], 'unq_review_category_id');
            $table->index(['category_id', 'review_category_id'], 'idx_review_category_id');
        });
        Schema::create('webbook_mall_reviews', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('product_id')->index();
            $table->integer('variant_id')->nullable()->index();
            $table->integer('customer_id')->nullable();
            $table->tinyInteger('rating');
            $table->string('user_hash');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('pros')->nullable();
            $table->text('cons')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
        });
        Schema::create('webbook_mall_category_reviews', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('review_id')->index();
            $table->integer('review_category_id')->index();
            $table->tinyInteger('rating');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
        });
        Schema::create('webbook_mall_category_review_totals', function ($table) {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('product_id')->nullable()->index();
            $table->integer('variant_id')->nullable();
            $table->integer('review_category_id');
            $table->decimal('rating', 3, 2);
        });
        Schema::table('webbook_mall_categories', function ($table) {
            if (! Schema::hasColumn('webbook_mall_categories', 'inherit_review_categories')) {
                $table->boolean('inherit_review_categories')->after('inherit_property_groups')->default(0);
            }
        });
        Schema::table('webbook_mall_products', function ($table) {
            $table->decimal('reviews_rating', 3, 2)->after('stock')->default(0);
        });
        Schema::table('webbook_mall_product_variants', function ($table) {
            $table->decimal('reviews_rating', 3, 2)->after('stock')->default(0);
        });

        if (Schema::hasTable('webbook_mall_index')) {
            Schema::table('webbook_mall_index', function ($table) {
                if (! Schema::hasColumn('webbook_mall_index', 'reviews_rating')) {
                    $table->decimal('reviews_rating', 3, 2);
                }
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('webbook_mall_review_categories');
        Schema::dropIfExists('webbook_mall_category_review_category');
        Schema::dropIfExists('webbook_mall_reviews');
        Schema::dropIfExists('webbook_mall_category_reviews');
        Schema::dropIfExists('webbook_mall_category_review_totals');
        Schema::table('webbook_mall_categories', function ($table) {
            $table->dropColumn(['inherit_review_categories']);
        });
        Schema::table('webbook_mall_products', function ($table) {
            $table->dropColumn(['reviews_rating']);
        });
        Schema::table('webbook_mall_product_variants', function ($table) {
            $table->dropColumn(['reviews_rating']);
        });
    }
}
