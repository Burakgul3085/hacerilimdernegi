<?php

namespace App\Filament\Support;

use App\Support\UploadRules;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;

class ContentUploads
{
    public static function image(string $name, string $directory, string $label = 'Görsel'): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->image()
            ->disk('public')
            ->directory($directory)
            ->acceptedFileTypes(UploadRules::IMAGE_MIMES)
            ->maxSize(UploadRules::maxImageKb());
    }

    public static function gallery(string $name, string $directory): FileUpload
    {
        return FileUpload::make($name)
            ->label('Fotoğraf ve videolar')
            ->multiple()
            ->appendFiles()
            ->reorderable()
            ->panelLayout('grid')
            ->openable()
            ->downloadable()
            ->disk('public')
            ->directory($directory)
            ->acceptedFileTypes(UploadRules::imageAndVideoMimes())
            ->maxSize(UploadRules::maxMediaKb())
            ->placeholder('Dosyalarınızı buraya bırakın veya çoklu seçin')
            ->helperText(UploadRules::galleryHelperText());
    }

    public static function mediaFile(string $name, string $directory, string $label = 'Dosya'): FileUpload
    {
        return FileUpload::make($name)
            ->label($label)
            ->disk('public')
            ->directory($directory)
            ->acceptedFileTypes(UploadRules::allMediaMimes())
            ->maxSize(UploadRules::maxMediaKb())
            ->helperText(UploadRules::mediaHelperText());
    }

    public static function withEditorUploads(RichEditor $editor): RichEditor
    {
        return $editor
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory('editor')
            ->fileAttachmentsVisibility('public')
            ->fileAttachmentsAcceptedFileTypes(UploadRules::imageAndVideoMimes())
            ->fileAttachmentsMaxSize(UploadRules::maxMediaKb());
    }
}
