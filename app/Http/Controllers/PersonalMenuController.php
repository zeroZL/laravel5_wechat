<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Input;
use App\PersonalMenu;
use App\Token;
use App\Log;
use League\Flysystem\Exception;


class PersonalMenuController extends Controller {

    public $url;
    public function __construct(){
        $this->middleware('token');
        $this->middleware('log');
        $app_id=Config::get('wechat.app_id');
        $app_secret=Config::get('wechat.app_secret');
        $this->url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$app_id&secret=$app_secret";
    }
    function curl_send(/*$url,*/$method='get',$data=[]){
        $method=strtolower($method);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if ($method == 'get') {
            $query = '?';
            $query.=http_build_query($data);
            $this->url .= $query;
            curl_setopt($curl, CURLOPT_URL, $this->url);
        }
        if ($method == 'post') {
            $data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            curl_setopt($curl, CURLOPT_URL, $this->url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if($method!='get'&& $method!='post'){
            throw new Exception('the method is invalid');
        }
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            throw new Exception(curl_error($curl),curl_errno($curl));
        }
        $http_status=curl_getinfo($curl,CURLINFO_HTTP_CODE);
        if($http_status!=200){
            throw new Exception('false');
        }
        curl_close($curl);
        $result=json_decode($result,true);
        if(array_key_exists('errcode',$result)&&$result['errcode']!=0){
            throw new Exception($result['errmsg'],$result['errcode']);
        }
        return $result;
    }
    public function updateAccess_token(){
        $method='get';
        $data=[];
        $returnArr = $this->curl_send($method, $data);
        $expires_in = $returnArr['expires_in'];
        $access_token = $returnArr['access_token'];
        Cache::put('access_token', $access_token, $expires_in / 120);
    }
    public function middleController(/*$url,*/$method='get',$data=[]){
        $access_token=Cache::get('access_token');
        if(is_null($access_token)) {
            $returnArr = $this->curl_send(/*$url,*/ $method, $data);
            $expires_in = $returnArr['expires_in'];
            $access_token = $returnArr['access_token'];
            Cache::put('access_token', $access_token, $expires_in / 120);

            return $access_token;
        }
        if(is_int(time()/3600)){
            $expiry_date=date("Y-m-d H:i:s",time()+3600);
        }else{
            $expiry_date=date("Y-m-d H",time()+3600).":"."00:00";
        }
        $result="获得access_token结果为:".$access_token."<br>"."有效期至：".$expiry_date;
        return $result;
        /*$result=['access_token'=>$access_token,'expiry_date'=>$expiry_date];
        $result=json_encode($result);
        return $result;*/
    }
    public function Create_personal_menu(){
    $access_token=Cache::get('access_token');
    if(!$access_token){
        $curl=curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxbddf3f4f5b1d570e&secret=44b7842e835e433b3873f6fa8d0773fc");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result=curl_exec($curl);
        $result=json_decode($result,true);
        if(array_key_exists('errcode',$result)&&$result['errcode']!=0){
            echo '错误信息为:'.$result['errmsg'];
        }
        curl_close($curl);
        $access_token=$result['access_token'];
        $expires_in=$result['expires_in'];
        /*$access_token=$result->access_token;
        $expires_in=$result->expires_in;*/
        Cache::put('access_token',$access_token,$expires_in / 60);
    }
    //$data=["button"=>[["name"=>"options","sub_button"=>[["type"=>"click","name"=>"choose1","key"=>"Sel_1"]]],["type"=>"click","name"=>"today music","key"=>"Td_Music"],["type"=>"click","name"=>"about us","key"=>"Ab_Us"]]];
    $data=array();
    /*for($i=1;$i<=6;$i++){
        $PersonalMenu=PersonalMenu::find($i);

        if($PersonalMenu->type ==null){
            $data["button"][]=["name"=>$PersonalMenu->name];
        }elseif($PersonalMenu->parent_id ==0){
            $data["button"][]=["type"=>$PersonalMenu->type,"name"=>$PersonalMenu->name,"key"=>$PersonalMenu->key];
        }elseif($PersonalMenu->parent_id !=0 && $PersonalMenu->type =="click"){
            $data["button"][0]["sub_button"][]=["type"=>$PersonalMenu->type,"name"=>$PersonalMenu->name,"key"=>$PersonalMenu->key];
        }elseif($PersonalMenu->parent_id !=0 && $PersonalMenu->type =="view"){
            $data["button"][0]["sub_button"][]=["type"=>$PersonalMenu->type,"name"=>$PersonalMenu->name,"url"=>$PersonalMenu->url];
        }
    }*/
    /*$personal_menu=PersonalMenu::find(1);
    $data["button"][]=["type"=>$personal_menu->type,"name"=>$personal_menu->name,"key"=>$personal_menu->key];
    $personal_menu=PersonalMenu::find(2);
    $data["button"][]=["type"=>$personal_menu->type,"name"=>$personal_menu->name,"url"=>$personal_menu->url];*/
    //var_dump($data);
    $personal_menu=new PersonalMenu();
    $menus=$personal_menu->where("parent_id","=","0")->get();
    foreach($menus as $menu){
        if($menu->parent_id==0) {
            //$data["button"][]=["name"=>$menu->name];
            $sons = $menu->Sons;
            if($sons->first()){
                echo $sons;
                $son_menu = array();
                foreach ($sons as $son) {
                    if ($son->key) {
                        $son_menu[] = ["type" => $son->type, "name" => $son->name, "key" => $son->key];
                    }
                    if ($son->url) {
                        $son_menu[] = ["type" => $son->type, "name" => $son->name, "url" => $son->url];
                    }
                }
                $data["button"][] = ["name" => $menu->name, "sub_button" => $son_menu];
                var_dump($data);
            } else {
                echo "OK";
                if ($menu->key) {
                    $data["button"][] = ["type"=>$menu->type,"name"=>$menu->name,"key" => $menu->key];
                }
                if ($menu->url) {
                    $data["button"][] = ["type"=>$menu->type,"name"=>$menu->name,"url" => $menu->url];
                }
                var_dump($data);
            }
        }
    }

    $data=json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    var_dump($data);
    $curl=curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$access_token);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    $result=curl_exec($curl);
    curl_close($curl);
    var_dump($result);
}
    /*        $data = '{
         "button":[
         {
              "name":"开始游戏",
              "sub_button":[
              {
                "type":"view",
                "name":"一个人？加入玩家群",
                "url":"http://game.91.com/"
              },
              {
                "type":"view",
                "name":"随机分配",
                "url":"https://www.baidu.com/"
              },
              {
                "type":"click",
                "name":"好友组队",
                "key":"team"
              }
              ]
          },
          {
               "type":"click",
               "name":"投票",
               "key":"Lei_vote"
          },
          {
               "name":"帮助",
               "sub_button":[
                {
                   "type":"view",
                   "name":"关于",
                   "url":"https://www.baidu.com/"
                },
                {
                   "type":"click",
                   "name":"赞一下我们",
                   "key":"V1001_GOOD"
                }]
           }]
        }';*/
        /*if($url==''||!is_array($params)){
            return "参数错误";
        }*/

       /* $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        if ($method == 'get') {
            $query = '?';
            foreach ($params as $key => $value) {
                $query .= $key . '=' . $value . '&';
            }
            $query = substr($query, 0, -1);
            $url .= $query;
            var_dump($url);
            curl_setopt($curl, CURLOPT_URL, $url);
        }
        if ($method == 'post') {
            $params = json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        }

        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            throw new Exception(curl_error($curl));
        }

        $result = curl_exec($curl);
        curl_close($curl);
        return $result;
    }*/

}

