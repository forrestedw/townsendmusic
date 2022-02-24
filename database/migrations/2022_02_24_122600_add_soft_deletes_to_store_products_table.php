<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->softDeletes();

            // we would also drop the 'deleted' column.
            // in the example then there are no deleted columns,
            // in reality we would create a command to migrate deleted
            // from the 'deleted' to 'deleted_at' column and then
            // do the drop.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('store_products', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
