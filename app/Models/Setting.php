<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'logo', 'name', 'phone', 'address', 'print_via_bluetooth', 'name_printer_local'
    ];

    protected static $isBluetoothEnabled = null;

    public static function isBluetoothEnabled(): bool
    {
        if (static::$isBluetoothEnabled === null) {
            // Ambil nilainya sekali saja dari database
            static::$isBluetoothEnabled = (bool) static::value('print_via_bluetooth');
        }

        return static::$isBluetoothEnabled;
    }

    protected static $islocalEnabled = null;

    public static function islocalEnabled(): bool
    {
        if (static::$islocalEnabled === null) {
            // Ambil nilainya sekali saja dari database
            static::$islocalEnabled = (bool) static::value('name_printer_local');
        }

        return static::$islocalEnabled;
    }

    protected static $settingExists = null;

    public static function alreadyExists(): bool
    {
        if (static::$settingExists === null) {
            // Kita pakai exists() karena lebih ringan dari count()
            static::$settingExists = static::exists(); 
        }
        
        return static::$settingExists;
    }
}