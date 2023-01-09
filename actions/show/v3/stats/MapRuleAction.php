<?php

/**
 * @copyright Copyright (C) 2015-2023 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\actions\show\v3\stats;

use LogicException;
use Yii;
use app\components\helpers\DateTimeHelper;
use app\models\Map3;
use app\models\Rule3;
use app\models\User;
use yii\base\Action;
use yii\db\Connection;
use yii\db\Query;
use yii\db\Transaction;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Request;

use function assert;
use function strcmp;
use function strnatcasecmp;

use const SORT_ASC;

final class MapRuleAction extends Action
{
    public ?User $user = null;

    /**
     * @inheritdoc
     * @return void
     */
    public function init()
    {
        parent::init();

        $request = Yii::$app->request;
        assert($request instanceof Request);

        $this->user = User::find()
            ->andWhere(['screen_name' => $request->get('screen_name')])
            ->limit(1)
            ->one();
        if (!$this->user) {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        }
    }

    public function run(): string
    {
        if (!($user = $this->user)) {
            throw new LogicException();
        }

        $data = Yii::$app->db->transaction(
            fn (Connection $db): array => [
                'mapStats' => $this->makeMapStats($db, $user),
                'maps' => $this->getMaps($db),
                'rules' => $this->getRules($db),
                'totalStats' => $this->makeTotalStats($db, $user),
                'user' => $user,
            ],
            Transaction::REPEATABLE_READ,
        );

        $c = $this->controller;
        assert($c instanceof Controller);
        return $c->render('stats/map-rule', $data);
    }

    /**
     * @return array<string, Map3>
     */
    private function getMaps(Connection $db): array
    {
        return ArrayHelper::asort(
            ArrayHelper::map(
                Map3::find()
                    ->andWhere(['and',
                        ['not', ['release_at' => null]],
                        ['<=', 'release_at', DateTimeHelper::isoNow()],
                    ])
                    ->all($db),
                'key',
                fn (Map3 $v): Map3 => $v,
            ),
            fn (Map3 $a, Map3 $b): int => 0
                ?: strnatcasecmp(Yii::t('app-map3', $a->name), Yii::t('app-map3', $b->name))
                ?: strnatcasecmp($a->name, $b->name)
                ?: strcmp($a->name, $b->name),
        );
    }

    /**
     * @return array<string, Rule3>
     */
    private function getRules(Connection $db): array
    {
        return ArrayHelper::map(
            Rule3::find()->orderBy(['rank' => SORT_ASC])->all(),
            'key',
            fn (Rule3 $v): Rule3 => $v,
        );
    }

    /**
     * @return array<string, array<string, array>> `[mapKey => [ruleKey => data]]`
     */
    private function makeMapStats(Connection $db, User $user): array
    {
        $q = (new Query())
            ->select([
                'map_id' => '{{%battle3}}.[[map_id]]',
                'map_key' => 'MAX({{%map3}}.[[key]])',
                'rule_id' => '{{%battle3}}.[[rule_id]]',
                'rule_key' => 'MAX({{%rule3}}.[[key]])',
                'battles' => 'COUNT(*)',
                'wins' => 'SUM(CASE WHEN {{%result3}}.[[is_win]] THEN 1 ELSE 0 END)',
                'kills' => 'AVG({{%battle3}}.[[kill]])',
                'kill_stddev' => 'STDDEV_POP({{%battle3}}.[[kill]])',
                'deaths' => 'AVG({{%battle3}}.[[death]])',
                'death_stddev' => 'STDDEV_POP({{%battle3}}.[[death]])',
            ])
            ->from('{{%battle3}}')
            ->innerJoin('{{%lobby3}}', '{{%battle3}}.[[lobby_id]] = {{%lobby3}}.[[id]]')
            ->innerJoin('{{%map3}}', '{{%battle3}}.[[map_id]] = {{%map3}}.[[id]]')
            ->innerJoin('{{%result3}}', '{{%battle3}}.[[result_id]] = {{%result3}}.[[id]]')
            ->innerJoin('{{%rule3}}', '{{%battle3}}.[[rule_id]] = {{%rule3}}.[[id]]')
            ->andWhere(['and',
                [
                    '{{%battle3}}.[[has_disconnect]]' => false,
                    '{{%battle3}}.[[is_deleted]]' => false,
                    '{{%battle3}}.[[user_id]]' => $user->id,
                    '{{%result3}}.[[aggregatable]]' => true,
                ],
                ['not', ['{{%battle3}}.[[lobby_id]]' => null]],
                ['not', ['{{%battle3}}.[[map_id]]' => null]],
                ['not', ['{{%battle3}}.[[result_id]]' => null]],
                ['not', ['{{%battle3}}.[[rule_id]]' => null]],
                ['not', ['{{%lobby3}}.[[key]]' => 'private']],
            ])
            ->groupBy([
                '{{%battle3}}.[[user_id]]',
                '{{%battle3}}.[[rule_id]]',
                '{{%battle3}}.[[map_id]]',
            ]);

        return ArrayHelper::map(
            $q->createCommand($db)->queryAll(),
            'rule_key',
            fn (array $row): array => $row,
            'map_key',
        );
    }

    /**
     * @return array<string, array> `[ruleKey => data]`
     */
    private function makeTotalStats(Connection $db, User $user): array
    {
        $q = (new Query())
            ->select([
                'rule_id' => '{{%battle3}}.[[rule_id]]',
                'rule_key' => 'MAX({{%rule3}}.[[key]])',
                'battles' => 'COUNT(*)',
                'wins' => 'SUM(CASE WHEN {{%result3}}.[[is_win]] THEN 1 ELSE 0 END)',
                'kills' => 'AVG({{%battle3}}.[[kill]])',
                'kill_stddev' => 'STDDEV_POP({{%battle3}}.[[kill]])',
                'deaths' => 'AVG({{%battle3}}.[[death]])',
                'death_stddev' => 'STDDEV_POP({{%battle3}}.[[death]])',
            ])
            ->from('{{%battle3}}')
            ->innerJoin('{{%lobby3}}', '{{%battle3}}.[[lobby_id]] = {{%lobby3}}.[[id]]')
            ->innerJoin('{{%map3}}', '{{%battle3}}.[[map_id]] = {{%map3}}.[[id]]')
            ->innerJoin('{{%result3}}', '{{%battle3}}.[[result_id]] = {{%result3}}.[[id]]')
            ->innerJoin('{{%rule3}}', '{{%battle3}}.[[rule_id]] = {{%rule3}}.[[id]]')
            ->andWhere(['and',
                [
                    '{{%battle3}}.[[has_disconnect]]' => false,
                    '{{%battle3}}.[[is_deleted]]' => false,
                    '{{%battle3}}.[[user_id]]' => $user->id,
                    '{{%result3}}.[[aggregatable]]' => true,
                ],
                ['not', ['{{%battle3}}.[[lobby_id]]' => null]],
                ['not', ['{{%battle3}}.[[map_id]]' => null]],
                ['not', ['{{%battle3}}.[[result_id]]' => null]],
                ['not', ['{{%battle3}}.[[rule_id]]' => null]],
                ['not', ['{{%lobby3}}.[[key]]' => 'private']],
            ])
            ->groupBy([
                '{{%battle3}}.[[user_id]]',
                '{{%battle3}}.[[rule_id]]',
            ]);

        return ArrayHelper::map(
            $q->createCommand($db)->queryAll(),
            'rule_key',
            fn (array $row): array => $row,
        );
    }
}
