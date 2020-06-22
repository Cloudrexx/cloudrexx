<?php

/**
 * If multiple PDFs are generated in the same request, define getCustomFonts()
 * only once.
 *
 * Only works if the else statement is defined, do not delete this.
 */
if (function_exists('getCustomFonts')) {
    getCustomFonts($this);
} else {
    /**
     * Load custom fonts for PDF generation
     *
     * @param mPdf $mPdf mPdf
     *
     * @return void
     */
    function getCustomFonts($mPdf)
    {
        $fontStyles = array(
            'Bold' => 'B',
            'Regular' => 'R',
            'Italic' => 'I',
            'BoldItalic' => 'BI'
        );

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $dir = $cx->getWebsiteDocumentRootPath() . \Cx\Core\Core\Controller\Cx::FOLDER_NAME_MEDIA
            . '/Pdf/ttfonts/';
        if (!file_exists($dir)) {
            return;
        }
        $fileNames = array_diff(scandir($dir), array('..', '.'));
        $newFonts = array();
        foreach ($fileNames as $file) {
            $splitFont = explode('-', $file);
            $fontName = $splitFont[0];
            $splitFontStyle = explode('.', $splitFont[1]);
            $fontStyle = $splitFontStyle[0];
            if (!isset($newFonts[strtolower($fontName)])) {
                $newFonts[strtolower($fontName)] = array();
            }

            if (isset($fontStyles[$fontStyle])) {
                $newFonts[strtolower($fontName)][$fontStyles[$fontStyle]] =
                    $fontName . '-' . $fontStyle . '.ttf';
            }
        }
        $mPdf->fontdata += $newFonts;
    }
    getCustomFonts($this);
}
