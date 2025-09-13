<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['conversation_id','message','sent_user','recieved_user'];
	public function conversation()
	{
	    return $this->belongsTo('App\Models\Conversation')->withDefault(function ($data) {
            foreach($data->getFillable() as $dt){
                $data[$dt] = __('Deleted');
            }
        });
	}
	
	
		public function sent()
	{
	    return $this->belongsTo('App\Models\User', 'sent_user')->withDefault();
	}

	public function recieved()
	{
	    return $this->belongsTo('App\Models\User', 'recieved_user')->withDefault();
	}
}
