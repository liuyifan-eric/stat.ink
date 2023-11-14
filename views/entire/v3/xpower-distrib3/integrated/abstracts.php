<?php

declare(strict_types=1);

use app\components\helpers\TypeHelper;
use app\components\widgets\Icon;
use app\models\Rule3;
use app\models\StatXPowerDistribAbstract3;
use yii\data\ArrayDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var View $this
 * @var array<int, Rule3> $rules
 * @var array<int, StatXPowerDistribAbstract3> $abstracts
 */

?>
<div class="mb-3">
  <?= GridView::widget([
    'dataProvider' => Yii::createObject([
      'class' => ArrayDataProvider::class,
      'allModels' => $rules,
      'pagination' => false,
      'sort' => false,
    ]),
    'emptyCell' => '-',
    'layout' => '{items}',
    'options' => ['class' => 'grid-view table-responsive'],
    'tableOptions' => ['class' => 'table table-striped table-bordered w-auto m-0'],
    'columns' => [
      [
        'label' => Yii::t('app', 'Mode'),
        'headerOptions' => ['class' => 'text-center'],
        'format' => 'raw',
        'value' => fn (Rule3 $model): string => implode(' ', [
          Icon::s3Rule($model),
          Html::encode(Yii::t('app-rule3', $model->name)),
        ]),
      ],
      [
        'encodeLabel' => false,
        'label' => implode(' ', [
          Icon::inkling(),
          Html::encode(Yii::t('app', 'Users')),
        ]),
        'headerOptions' => ['class' => 'text-center'],
        'format' => 'integer',
        'value' => fn (Rule3 $model): ?int => $abstracts[$model->id]?->users,
        'contentOptions' => ['class' => 'text-right'],
      ],
      [
        'label' => Yii::t('app', 'Average'),
        'headerOptions' => ['class' => 'text-center'],
        'format' => ['decimal', 1],
        'value' => fn (Rule3 $model): ?float => TypeHelper::floatOrNull(
          $abstracts[$model->id]?->average,
        ),
        'contentOptions' => ['class' => 'text-right'],
      ],
      [
        'label' => Yii::t('app', 'Std Dev'),
        'headerOptions' => ['class' => 'text-center'],
        'format' => ['decimal', 1],
        'value' => fn (Rule3 $model): ?float => TypeHelper::floatOrNull(
          $abstracts[$model->id]?->stddev,
        ),
        'contentOptions' => ['class' => 'text-right'],
      ],
      [
        'label' => Yii::t('app', 'Median'),
        'headerOptions' => ['class' => 'text-center'],
        'format' => ['decimal', 1],
        'value' => fn (Rule3 $model): ?float => TypeHelper::floatOrNull(
          $abstracts[$model->id]?->median,
        ),
        'contentOptions' => ['class' => 'text-right'],
      ],
    ],
  ]) . "\n" ?>
</div>
