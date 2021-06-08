<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use ApiPlatform\Core\Tests\Fixtures\TestBundle\Models\FourthLevel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use WouterJ\EloquentBundle\Facade\Schema;

class CreateThirdLevelTable extends Migration
{
    public function up(): void
    {
        Schema::create('third_levels', function (Blueprint $table) {
            $table->id();
            $table->integer('level');
            $table->boolean('test');
            $table->foreignIdFor(FourthLevel::class)->nullable(true)->constrained();
            $table->foreignIdFor(FourthLevel::class, 'bad_fourth_level_id')->nullable(true)->constrained();
        });
    }

    public function down(): void
    {
        Schema::drop('third_levels');
    }
}
