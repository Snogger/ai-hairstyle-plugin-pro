<?php
/**
 * Dedicated class for all prompt templates.
 */
class AI_Hairstyle_Prompts {
    public static function get_main_prompt( $color = '' ) {
        $color_part = $color ? " in $color shade" : '';
        return "Precisely overlay only the hairstyle from references onto the user's face/head. Keep exact head shape, face features, skin tone, and expression. Fit hair naturally. Subtle overall improvements. Blur background 15-20%. Better lighting. Ultra-realistic photo. Do not change clothes or body.$color_part";
    }
}