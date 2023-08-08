<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class UserNotificaitonSetup extends Model
{
    //

    protected $primaryKey    = 'id';
    protected $table         = 'user_notification_setup';
    public $timestamps       = true;

    public function zoneData()
    {
        return $this->hasOne(UsZones::class, 'zone', 'zone');
    }
}
