<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lastfm;
use Media;

/**
 * @property string path
 */
class Song extends Model
{
    protected $guarded = [];

    /**
     * Attributes to be hidden from JSON outputs.
     * Here we specify to hide lyrics as well to save some bandwidth (actually, lots of it).
     * Lyrics can then be queried on demand.
     *
     * @var array
     */
    protected $hidden = ['lyrics', 'created_at', 'updated_at', 'path', 'mtime'];

    /**
     * @var array
     */
    protected $casts = [
        'length' => 'float',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    public function album()
    {
        return $this->belongsTo(Album::class);
    }

    public function playlists()
    {
        return $this->belongsToMany(Playlist::class);
    }

    /**
     * Scrobble the song using Last.fm service.
     *
     * @param string $timestamp The UNIX timestamp in which the song started playing.
     *
     * @return mixed
     */
    public function scrobble($timestamp)
    {
        // Don't scrobble the unknown guys. No one knows them.
        if ($this->album->artist->isUnknown()) {
            return false;
        }

        // If the current user hasn't connected to Last.fm, don't do shit.
        if (!$sessionKey = auth()->user()->getLastfmSessionKey()) {
            return false;
        }

        return Lastfm::scrobble(
            $this->album->artist->name,
            $this->title,
            $timestamp,
            $this->album->name === Album::UNKNOWN_NAME ? '' : $this->album->name,
            $sessionKey
        );
    }

    /**
     * Get a Song record using its path.
     *
     * @param string $path
     *
     * @return Song|null
     */
    public static function byPath($path)
    {
        return self::find(Media::getHash($path));
    }

    /**
     * Scope a query to only include songs in a given directory.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $path  Full path of the directory
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInDirectory($query, $path)
    {
        // Make sure the path ends with a directory separator.
        $path = rtrim(trim($path), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return $query->where('path', 'LIKE', "$path%");
    }

    /**
     * Sometimes the tags extracted from getID3 are HTML entity encoded.
     * This makes sure they are always sane.
     *
     * @param $value
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = html_entity_decode($value);
    }

    /**
     * Some songs don't have a title.
     * Fall back to the file name (without extension) for such.
     *
     * @param  $value
     *
     * @return string
     */
    public function getTitleAttribute($value)
    {
        return $value ?: pathinfo($this->path, PATHINFO_FILENAME);
    }

    /**
     * Prepare the lyrics for displaying.
     *
     * @param $value
     *
     * @return string
     */
    public function getLyricsAttribute($value)
    {
        return nl2br($value);
    }
}
