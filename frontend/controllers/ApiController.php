<?php
namespace frontend\controllers;

use Yii;
use common\models\LoginForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;
use yii\filters\ContentNegotiator;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;


/**
 * Site controller
 */
class ApiController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'only' => ['dashboard'],
        ];
        $behaviors['contentNegotiator'] = [
            'class' => ContentNegotiator::className(),
            'formats' => [
                'application/json' => Response::FORMAT_JSON,
            ],
        ];
        $behaviors['access'] = [
            'class' => AccessControl::className(),
            'only' => ['dashboard'],
            'rules' => [
                [
                    'actions' => ['dashboard'],
                    'allow' => true,
                    'roles' => ['@'],
                ],
            ],
        ];
        return $behaviors;
    }

    public function actionLogin()
    {
        $model = new LoginForm();

        if ($model->load(Yii::$app->getRequest()->getBodyParams(), '') && $model->login()) {
            return ['access_token' => Yii::$app->user->identity->getAuthKey()];
        } else {
            $model->validate();
            return $model;
        }
    }
    
    public function actionRegister()
    {
        $model = new SignupForm();
        if($model->load(Yii::$app->getRequest()->getBodyParams(), '') && $model->validate()) {
            
            /*if($model->sendEmail(Yii::$app->params['adminEmail'])) {
                        $response = [
                            'flash' => [
                                'class' => 'success',
                                'message' => 'Thank you for contacting us. We will respond to you as soon as possible.',
                            ]
                        ];
                    } else {
                        $response = [
                            'flash' => [
                                'class' => 'error',
                                'message' => 'There was an error sending email.',
                            ]
                        ];
                    }*/
            if($model->signup()){
                    $response = [
                        'flash' => [
                            'class' => 'success',
                            'message' => 'Thank you for contacting us. We will respond to you as soon as possible.',
                        ]
                    ];
            }else{
               $response = [
                    'flash' => [
                        'class' => 'error',
                        'message' => 'There was an error on saving data.',
                        'detail'=> $model->errors,
                    ]
                ]; 
            }
            return $response;
        }else{
            $model->validate();
            return $model;
        }
    }

    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->getRequest()->getBodyParams(), '') && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                $response = [
                    'flash' => [
                        'class' => 'success',
                        'message' => 'Thank you for contacting us. We will respond to you as soon as possible.',
                    ]
                ];
            } else {
                $response = [
                    'flash' => [
                        'class' => 'error',
                        'message' => 'There was an error sending email.',
                    ]
                ];
            }
            return $response;
        } else {
            $model->validate();
            return $model;
        }
    }
}
