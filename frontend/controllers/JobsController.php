<?php
namespace frontend\controllers;

use Yii;
use yii\rest\Controller;

use frontend\models\JobList;


class JobsController extends Controller
{
    public function actionIndex()
    {
        $jobs = JobList::find()->asArray()->alias('jl')
                ->select('jl.job_id AS jobId, jl.title AS jobTitle, jl.url_alias AS urlAlias, jl.detail AS jobDetail,
                        jl.position AS category, jl.end_client AS endClient, jl.updated_at AS updatedAt,
                        jl.company_id AS companyId, mc.name AS companyName')
                
                ->leftJoin('master_company AS mc','mc.id = jl.company_id')
                
                ->where(['job_status'=>7])
                ->andWhere('start_date <= CURDATE() AND end_date >= CURDATE() ')
                ->orderBy('jl.updated_at DESC ,jl.job_id DESC')
                ->limit(40)
                ->all();
        
        
        
        $response = [
            'user' => Yii::$app->user->identity,
            'jobs' => $jobs,
        ];

        return $response;
    }
}
