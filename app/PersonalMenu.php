<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class PersonalMenu extends Model {

    protected $fillable=['name','type','parent_id','url','key'];
    public function Sons(){
        return $this->hasMany('App\PersonalMenu','parent_id','id');
    }
    public function parent(){
        return $this->belongsTo('App\PersonalMenu','parent_id','id');
    }

}
