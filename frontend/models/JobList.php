<?php

namespace frontend\models;

use Yii;
use sharad\miscLib\ProfGeneric as pg;

class JobList extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'job_list';
    }

    
    public function rules()
    {
        return [
            [['title', 'detail', 'position', 'assignment_type', 'opening_count', 
                'company_id', 'end_client', 'client_code', 'resource_type', 'interview_type',  'job_status',
                'min_salary','max_salary','salary_type', 'country', 'state', 
                'city','recruiter'], 'required'],
            [['detail'], 'string'],
            [['assignment_type', 'opening_count', 'country', 
                'state', 'created_by'], 'integer'],
            
            [['min_salary'], 'compare', 'compareAttribute'=>'max_salary', 'operator'=>'<='],
            
            [['min_salary','max_salary'], 'number'],
            [['start_date', 'end_date','posted_date', 'created_at', 'updated_at','keywords'], 'safe'],
            [['title', 'instruction'], 'string', 'max' => 250],
            [['title'], 'string', 'max' => 75],
            [['position'], 'string', 'max' => 50,'min'=>1],
            [['end_client'], 'string', 'max' => 200],
            [['client_code'], 'string', 'max' => 15, 'tooLong'=>'Maximum 15 characters allowed'],
            [['salary_type'], 'string', 'max' => 50],
        ];
    }

    
    public function attributeLabels()
    {
        return [
            'job_id' => 'Job ID',
            'title' => 'Job Title',
            'detail' => 'Job Description',
            'keywords' => 'Additional meta tags',
            'position' => 'Position Title',
            'assignment_type' => 'Assignment Type',
            'opening_count' => 'Number of positions',
            'company_id'=>'Client',
            'end_client' => 'End Client',
            'client_code' => 'Job ID',
            'resource_type' => 'Resource Type',
            'interview_type' => 'Initial Interview type',
            'min_salary' => 'Minimum Salary',
            'max_salary' => 'Maximum Salary',
            'salary_type' => 'Salary Type',
            'start_date' => 'Job listing date',
            'end_date' => 'Job expires on',
            'duration'=>'Duration (In Month)',
            'for_local_only' => 'For Local Only',
            'instruction' => 'Any Special Instructions',
            'country' => 'Country',
            'state' => 'State',
            'city' => 'City',
            'job_status' => 'Job Status',
            'posted_date' => 'Posted Date',
            'created_by' => 'Recruiter',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
    
    public static function getTotalBonus($option = []){
        
        $return = ['numberOfJob'=>0,'availableBonus'=>0];
        /*
         {"industry":"AERODEF",
         * "department":"CSTMRSRV2",
         * "companyType":"",
         * "experienceLevel":"",
         * "employmentType":"",
         * "keyword":"rttgre",
         * "assignmentType":"","payRate":"","postal":"",
         * "radius":0,"country":0,"state":0,"city":0}
         */
        $whereCondition = [];
        
        if(isset($option['searchCriteria']) && $option['searchCriteria']){
            // $option['searchCriteria'] must be a JSON object
            $data = json_decode($option['searchCriteria']);
            
            /*
            * Build query with search parameters  
            */
            if(isset($data->industry) && $data->industry){
                if(preg_match('/^[\w]+$/',$data->industry)){
                    $whereCondition[] = ' jl.industry = \''.$data->industry.'\'';
                }else{
                    $whereCondition[] = ' jl.industry = \'(invalid)\' ';
                }
            }

            if(isset($data->department) && $data->department){
                if(preg_match('/^[\w]+$/',$data->department)){
                    $whereCondition[] = ' jl.department = \''.$data->department.'\' ';
                }else{
                    $whereCondition[] = ' jl.department = \'(invalid)\' '; // this will force no result if search request is invalid
                }
            }

            if(isset($data->companyType) && $data->companyType){
                if(preg_match('/^[\w]+$/',$data->companyType))
                {
                    $whereCondition[] = ' comp.company_type = \''.$data->companyType.'\' ';
                }else{
                    $whereCondition[] = ' comp.company_type = \'(invalid)\' '; // this will force no result if search request is invalid
                }
            }

            if(isset($data->experienceLevel) && $data->experienceLevel){
                if(isset(Yii::$app->params['experienceLevel'][$data->experienceLevel]))
                {
                    $whereCondition[] = ' jl.experience_level = \''.$data->experienceLevel.'\' ';
                }else{
                    $whereCondition[] = ' jl.experience_level = \'(invalid)\' '; // this will force no result if search request is invalid
                }   
            }

            if(isset($data->employmentType) && $data->employmentType){
                if(preg_match('/^[\w]+$/',$data->employmentType)){
                    $whereCondition[] = ' jl.employment_type = \''.$data->employmentType.'\' ';
                }else{
                    $whereCondition[] = ' jl.employment_type = \'(invalid)\' ';
                }
            }

            if(isset($data->assignmentType) && $data->assignmentType){
                if(preg_match('/^[\w]+$/',$data->assignmentType))
                {
                    $whereCondition[] = ' jl.assignment_type = \''.$data->assignmentType.'\' ';
                }else{
                    $whereCondition[] = ' jl.assignment_type = \'(invalid)\' ';
                }
            }

            if(isset($data->payRate) && $data->payRate){
                if( preg_match('/^[\d]+$/', intval($data->payRate)) ){
                    $whereCondition[] = ' jl.min_salary >= \''.$data->payRate.'\' ';
                }else{
                    $whereCondition[] = ' jl.min_salary = \'(invalid)\' ';
                }
            }

            if(isset($data->keyword) && $data->keyword){
                $keWrds = preg_replace('/[\s]+/im',' ',preg_replace('/[^\w\#\.]/im', ' ', $data->keyword));
                $whereCondition[] = ' (
                    match(js.job_string) against("'.$keWrds.'" IN BOOLEAN MODE)
                    OR
                    match(comp.name) against("'.$keWrds.'" IN BOOLEAN MODE)
                    OR
                    comp.name LIKE \'%'.pg::filterContent($keWrds).'%\'
                    )';
            }

            if(isset($data->postal) && $data->postal){

                $city = CityPostalMaster::find()->where(['postal_code'=>$data->postal])->one();
                if(!empty($city)){
                    $radius = (isset($data->radius) && $data->radius && $data->radius != '') ? $data->radius : 0;
                    $geoLocation = CityMaster::find()->where(['row_id'=>$city->city_id])->one();
                    if($radius){

                        if($radius == '100'){ $operater = '>';}else{$operater = '<=';}
                        $whereCondition[] = ' ROUND(
                                        (6371 * acos( cos( radians(\''.$geoLocation->geo_lat.'\') ) * cos( radians( mcjl.geo_lat) )
                                        * cos( radians( \''.$geoLocation->geo_long.'\' ) - radians(mcjl.geo_long) )
                                        + sin( radians(\''.$geoLocation->geo_lat.'\') ) * sin( radians(mcjl.geo_lat) ) )),2
                                    ) '.$operater.' '.$radius;


                    }else{
                        //IF radius is blank and zip is filled then show 0 record 
                        $whereCondition[] = ' jl.job_id = \'(invalid)\' ';
                    }
                }else{
                    //IF if city id is not exist is DB then show 0 record 
                    $whereCondition[] = ' jl.job_id = \'(invalid)\' ';
                }
            }else{

                if(isset($data->country) && $data->country){
                    $whereCondition[] = ' jl.country IN ('.$data->country.') ';
                }

                if(isset($data->state) && $data->state){
                    $whereCondition[] = ' jl.state IN ('.$data->state.') ';
                }

                if(isset($data->city) && $data->city){
                    $whereCondition[] = ' jl.city IN ('.$data->city.') ';
                }
            }
            
        }
        
        $whereCondition[] = ' jl.start_date <= CURDATE() AND jl.end_date >= CURDATE() ';
        $whereCondition[] = ' jl.job_status = 7 ';
        $query = new \yii\db\Query;
        $query->select(['sum(jb.bonus_amount) as `totalBonus`'])
                ->from('job_list jl')
                ->leftJoin('`job_search` AS `js`', 'js.job_id = jl.job_id')
                ->leftJoin('`master_company` AS `comp`', 'comp.id = jl.company_id')
                ->leftJoin('master_city mcjl', 'mcjl.row_id = jl.city')
                ->leftJoin('job_bonus AS jb','jb.job_id = jl.job_id AND jb.bonus_type = \'referrer\' ')
                ->where(implode(' AND ',$whereCondition))
                ->groupBy('jl.job_id');
        
        if($query->count()){
            $command = $query->createCommand();
            $data = $command->queryAll();
            $totalBonus = 0;
            //echo '<pre>'; print_r($data); die();
            foreach ($data as $key=>$value) {
                
                $totalBonus += $value['totalBonus'];
            }
            $return['availableBonus'] = $totalBonus;
            $return['numberOfJob'] = $query->count();
        }
        
        return $return;
    }
    
}
