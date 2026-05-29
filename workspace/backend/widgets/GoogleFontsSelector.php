<?php

namespace backend\widgets;

use tws\widgets\typeahead\Typeahead;
use Yii;
use yii\helpers\Html;
use yii\web\JsExpression;

/**
 * Google Fonts Selector Widget
 * Provides a typeahead input for UTF-8 compatible Google Fonts with manual input support
 * Especially suitable for Romanian diacritics
 */
class GoogleFontsSelector extends Typeahead
{
    /**
     * @var array Additional options for the input field
     */
    public $options = [];
    
    /**
     * UTF-8 compatible Google Fonts (especially good for Romanian diacritics)
     * These fonts support Latin Extended-A character set including: ă, â, î, ș, ț
     */
    private static $utf8Fonts = [
        'Roboto' => 'Roboto',
        'Open Sans' => 'Open Sans',
        'Lato' => 'Lato',
        'Montserrat' => 'Montserrat',
        'Source Sans Pro' => 'Source Sans Pro',
        'Raleway' => 'Raleway',
        'PT Sans' => 'PT Sans',
        'Ubuntu' => 'Ubuntu',
        'Poppins' => 'Poppins',
        'Merriweather' => 'Merriweather',
        'Playfair Display' => 'Playfair Display',
        'Oswald' => 'Oswald',
        'Roboto Condensed' => 'Roboto Condensed',
        'Roboto Slab' => 'Roboto Slab',
        'Lora' => 'Lora',
        'Noto Sans' => 'Noto Sans',
        'Noto Serif' => 'Noto Serif',
        'PT Serif' => 'PT Serif',
        'Droid Sans' => 'Droid Sans',
        'Droid Serif' => 'Droid Serif',
        'Arimo' => 'Arimo',
        'Tinos' => 'Tinos',
        'Cousine' => 'Cousine',
        'Crimson Text' => 'Crimson Text',
        'Libre Baskerville' => 'Libre Baskerville',
        'Libre Franklin' => 'Libre Franklin',
        'Work Sans' => 'Work Sans',
        'Fira Sans' => 'Fira Sans',
        'Fira Code' => 'Fira Code',
        'Inconsolata' => 'Inconsolata',
        'Nunito' => 'Nunito',
        'Rubik' => 'Rubik',
        'Cairo' => 'Cairo',
        'Titillium Web' => 'Titillium Web',
        'Muli' => 'Muli',
        'Hind' => 'Hind',
        'Dosis' => 'Dosis',
        'Varela Round' => 'Varela Round',
        'Bitter' => 'Bitter',
        'Cabin' => 'Cabin',
        'Quicksand' => 'Quicksand',
        'Josefin Sans' => 'Josefin Sans',
        'Exo 2' => 'Exo 2',
        'Anton' => 'Anton',
        'Bebas Neue' => 'Bebas Neue',
        'Comfortaa' => 'Comfortaa',
        'Dancing Script' => 'Dancing Script',
        'Indie Flower' => 'Indie Flower',
        'Pacifico' => 'Pacifico',
        'Shadows Into Light' => 'Shadows Into Light',
        'Amatic SC' => 'Amatic SC',
        'Caveat' => 'Caveat',
        'Permanent Marker' => 'Permanent Marker',
        'Righteous' => 'Righteous',
        'Satisfy' => 'Satisfy',
        'Kalam' => 'Kalam',
        'Courgette' => 'Courgette',
        'Lobster' => 'Lobster',
        'Arial' => 'Arial (System Font)',
        'Helvetica' => 'Helvetica (System Font)',
        'Georgia' => 'Georgia (System Font)',
        'Times New Roman' => 'Times New Roman (System Font)',
        'Verdana' => 'Verdana (System Font)',
        'Courier New' => 'Courier New (System Font)',
    ];
    
    /**
     * @inheritdoc
     */
    public function init()
    {
        // Set default placeholder before parent init
        if (!isset($this->options['placeholder'])) {
            $this->options['placeholder'] = Yii::t('backend', 'Type or select a font...');
        }
        
        // Prepare font data for typeahead BEFORE parent::init()
        // Sort fonts alphabetically by display name
        $sortedFonts = static::$utf8Fonts;
        asort($sortedFonts); // Sort by value (display name) alphabetically
        
        $fontData = [];
        foreach ($sortedFonts as $key => $value) {
            $fontData[] = [
                'display' => $value,
                'id' => $key,
                'name' => $key,
            ];
        }
        
        // Configure Typeahead client options BEFORE parent::init()
        // This is critical because parent::init() calls registerAssets() which uses clientOptions
        $defaultOptions = [
            'minLength' => 0,
            'maxItem' => count($fontData), // Show all fonts - set to total count
            'hint' => true,
            'cancelButton' => false,
            'dynamic' => false,
            'searchOnFocus' => true,
            'backdrop' => [
                'background-color' => '#ffffff',
                'opacity' => '0.4',
            ],
            'source' => [
                'fonts' => [
                    'display' => ['display'],
                    'data' => $fontData,
                ],
            ],
            'callback' => [
                'onInit' => new JsExpression('function(node) {}'),
                'onClick' => new JsExpression('function(node, a, item, event) {
                    if (item && item.name) {
                        var fontName = item.name;
                        var $input = jQuery(node).closest(".typeahead__container").find("input");
                        $input.val(fontName);
                        $input.trigger("change");
                    }
                }'),
            ],
        ];
        
        $this->clientOptions = array_merge($defaultOptions, $this->clientOptions);
        
        // Now call parent init which will register assets with our clientOptions
        parent::init();
        
        // Register CSS for scrollable suggestions
        $view = $this->getView();
        $css = "
            .typeahead__container .typeahead__list {
                max-height: 300px;
                overflow-y: auto;
                overflow-x: hidden;
            }
            .typeahead__container .typeahead__list::-webkit-scrollbar {
                width: 8px;
            }
            .typeahead__container .typeahead__list::-webkit-scrollbar-track {
                background: #f1f1f1;
                border-radius: 4px;
            }
            .typeahead__container .typeahead__list::-webkit-scrollbar-thumb {
                background: #888;
                border-radius: 4px;
            }
            .typeahead__container .typeahead__list::-webkit-scrollbar-thumb:hover {
                background: #555;
            }
        ";
        $view->registerCss($css);
    }
    
    /**
     * Get the Google Fonts URL for the selected font
     * @param string $fontName
     * @return string|null
     */
    public static function getGoogleFontsUrl($fontName)
    {
        if (empty($fontName) || static::isSystemFont($fontName)) {
            return null;
        }
        
        // Replace spaces with + for Google Fonts API
        $fontNameEncoded = str_replace(' ', '+', $fontName);
        
        // Add Latin Extended subset for Romanian diacritics
        return "https://fonts.googleapis.com/css2?family={$fontNameEncoded}:wght@300;400;500;600;700&subset=latin,latin-ext&display=swap";
    }
    
    /**
     * Check if font is a system font
     * @param string $fontName
     * @return bool
     */
    public static function isSystemFont($fontName)
    {
        $systemFonts = ['Arial', 'Helvetica', 'Georgia', 'Times New Roman', 'Verdana', 'Courier New'];
        return in_array($fontName, $systemFonts);
    }
    
    /**
     * Get CSS font-family value
     * @param string $fontName
     * @return string
     */
    public static function getFontFamilyCss($fontName)
    {
        if (empty($fontName)) {
            return '';
        }
        
        // If it's a system font, return as-is
        if (static::isSystemFont($fontName)) {
            return $fontName;
        }
        
        // For Google Fonts, wrap in quotes and add fallback
        return '"' . $fontName . '", sans-serif';
    }
}

