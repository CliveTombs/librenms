<?php
/**
 * Eventlog.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    LibreNMS
 * @link       http://librenms.org
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace App\Models;

use Carbon\Carbon;

class Eventlog extends BaseModel
{
    protected $table = 'eventlog';
    protected $primaryKey = 'event_id';
    public $timestamps = false;
    protected $fillable = ['datetime', 'message', 'type', 'reference', 'username', 'severity'];

    // ---- Helper Functions ----

    /**
     * Log events to the event table
     *
     * @param string $text message describing the event
     * @param Device $device related device
     * @param string $type brief category for this event. Examples: sensor, state, stp, system, temperature, interface
     * @param int $severity 1: ok, 2: info, 3: notice, 4: warning, 5: critical, 0: unknown
     * @param int $reference the id of the referenced entity.  Supported types: interface
     */
    public static function log($text, $device = null, $type = null, $severity = 2, $reference = null)
    {
        $log = new static([
            'reference' => $reference,
            'type' => $type,
            'datetime' => Carbon::now(),
            'severity' => $severity,
            'message' => $text,
            'username'  => (class_exists('\Auth') && \Auth::check()) ? \Auth::user()->username : '',
        ]);

        if ($device instanceof Device) {
            $device->eventlogs()->save($log);
        } else {
            $log->save();
        }
    }

    // ---- Query scopes ----

    public function scopeHasAccess($query, User $user)
    {
        return $this->hasDeviceAccess($query, $user);
    }

    // ---- Define Relationships ----

    /**
     * Returns the device this entry belongs to.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function device()
    {
        return $this->belongsTo('App\Models\Device', 'device_id');
    }

    public function related()
    {
        return $this->morphTo('related', 'type', 'reference');
    }
}
