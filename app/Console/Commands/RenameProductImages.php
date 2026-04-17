<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
class RenameProductImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rename:product-images';

    /**
     * The console command description.
     *
     * @var string
     */


    protected $description = 'Переименовывает фото: Комбо В-52 Люкс_ → Комбо в-52 люкс_';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $files = collect(Storage::disk('public')->files('products/combo-uni-2'));

        $files->each(function ($file) {
            $filename = basename($file);

            if (str_starts_with($filename, 'Комбо уни-1_')) {
                $newFilename = str_replace('Комбо уни-1_', 'Комбо уни-2_', $filename);
                $newPath = 'products/combo-uni-2/' . $newFilename;

                Storage::disk('public')->move($file, $newPath);

                $this->info("Переименовано: $filename → $newFilename");
            }
        });

        $this->info('Готово!');
    }
}
