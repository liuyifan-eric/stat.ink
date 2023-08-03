<?php

declare(strict_types=1);

use app\models\Event3StatsPower;
use app\models\Event3StatsPowerHistogram;
use app\models\Event3StatsPowerPeriodHistogram;
use app\models\EventPeriod3;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var Event3StatsPower $abstract
 * @var Event3StatsPowerHistogram[] $histogram
 * @var Event3StatsPowerPeriodHistogram[] $periodHistogram
 * @var EventPeriod3[] $periods
 * @var View $this
 */

if (
  $abstract->histogram_width < 1 ||
  !$histogram
) {
  return;
}

$items = [];

$items[] = [
  'active' => true,
  'label' => Yii::t('app', 'Total'),
    'content' => $this->render('histograms/total', [
    'abstract' => $abstract,
    'histogram' => ArrayHelper::map($histogram, 'class_value', 'battles'),
  ]),
];

if (count($periods) > 1 && $periodHistogram) {
  $items[] = [
    'label' => Yii::t('app', 'Stacked'),
    'content' => $this->render('histograms/stacked', [
      'abstract' => $abstract,
      'histogram' => $periodHistogram,
      'periods' => $periods,
    ]),
  ];

  foreach ($periods as $i => $period) {
    $thisHistogram = array_filter(
      $periodHistogram,
      fn ($v): bool => $v->period_id === $period->id,
    );
    if ($thisHistogram) {
      $items[] = [
        'label' => mb_chr(0x2460 + $i),
        'content' => $this->render('histograms/period', [
          'label' => mb_chr(0x2460 + $i),
          'histogram' => ArrayHelper::map($thisHistogram, 'class_value', 'battles'),
        ]),
      ];
    }
  }
}

echo Html::tag(
  'div',
  Tabs::widget([
    'items' => $items,
    'tabContentOptions' => [
      'class' => 'tab-content mt-1',
    ],
  ]),
  ['class' => 'mb-3'],
);
