<?php
    namespace PontoMega\Plugin\Content\PaypalButton\Extension;

    defined('_JEXEC') or die;

    use Joomla\CMS\Factory;
    use Joomla\CMS\Language\Text;
    use Joomla\CMS\Plugin\CMSPlugin;
    use Joomla\Event\Event;

    /**
 * PayPal Button Content Plugin
 */
    class PaypalButton extends CMSPlugin
    {
    /**
     * Load the language file on instantiation.
     *
     * @var    boolean
     * @since  1.0.0
     */
    protected $autoloadLanguage = true;

    /**
     * Preparation of content.
     *
     * @param   string   $context  The context of the content being passed to the plugin.
     * @param   object   &$row     The article object.
     * @param   object   &$params  The article params.
     * @param   integer  $page     The 'page' number.
     *
     * @return  void
     */
    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        // Don't run in backend
        if (Factory::getApplication()->isClient('administrator')) {
            return;
        }

        // Replace manual tags {paypal name="..." price="..."}
        $regex = '/\{paypal\s+(.*?)\}/i';
        if (isset($row->text) && preg_match_all($regex, $row->text, $matches)) {
            foreach ($matches[0] as $i => $tag) {
                $attributes = $this->parseAttributes($matches[1][$i]);
                $buttonHtml = $this->renderButton($attributes);
                $row->text  = str_replace($tag, $buttonHtml, $row->text);
            }
        }

        // Auto-insertion if enabled
        if ($this->params->get('auto_insert_enabled', 0)) {
            $categories = (array) $this->params->get('categories', []);
            if (in_array($row->catid, $categories)) {
                $this->handleAutoInsert($row, $context);
            }
        }
    }

    /**
     * Event after title.
     */
    public function onContentAfterTitle($context, &$item, &$params, $page = 0)
    {
        if ($this->params->get('position') === 'after_title') {
            return $this->getAutoInsertOutput($item, $context);
        }
        return '';
    }

    /**
     * Event after content.
     */
    public function onContentAfterDisplay($context, &$item, &$params, $page = 0)
    {
        if ($this->params->get('position') === 'after_content') {
            return $this->getAutoInsertOutput($item, $context);
        }
        return '';
    }

    /**
     * Event before content.
     */
    public function onContentBeforeDisplay($context, &$item, &$params, $page = 0)
    {
        if ($this->params->get('position') === 'before_content') {
            return $this->getAutoInsertOutput($item, $context);
        }
        return '';
    }

    /**
     * Logic for auto-insertion.
     */
    private function getAutoInsertOutput($item, $context)
    {
        // Don't run in backend
        if (Factory::getApplication()->isClient('administrator')) {
            return '';
        }

        if (! $this->params->get('auto_insert_enabled', 0)) {
            return '';
        }

        $categories = (array) $this->params->get('categories', []);
        if (! in_array($item->catid, $categories)) {
            return '';
        }

        // Get data from custom fields or metadata
        $price = $this->getFieldValue($item, $this->params->get('price_field', 'price'));
        $name  = $this->params->get('product_name_field') === 'article_title'
            ? $item->title
            : $this->getFieldValue($item, $this->params->get('product_name_field'));

        if (! $price) {
            return '';
        }

        return $this->renderButton([
            'name'  => $name,
            'price' => $price,
        ]);
    }

    /**
     * Parse attributes from {paypal attr="value"}
     */
    private function parseAttributes($string)
    {
        $attributes = [];
        $pattern    = '/(\w+)\s*=\s*["\']([^"\']+)["\']/';
        if (preg_match_all($pattern, $string, $matches)) {
            foreach ($matches[1] as $i => $key) {
                $attributes[$key] = $matches[2][$i];
            }
        }
        return $attributes;
    }

    /**
     * Get value from custom field.
     */
    private function getFieldValue($item, $fieldName)
    {
        if (isset($item->jcfields)) {
            foreach ($item->jcfields as $field) {
                if ($field->name === $fieldName || $field->id == $fieldName) {
                    return $field->rawvalue ?? $field->value;
                }
            }
        }
        return '';
    }

    /**
     * Render the PayPal button.
     */
    private function renderButton($data)
    {
        $paypalEmail = $this->params->get('paypal_email');
        $sandbox     = $this->params->get('sandbox', 1);
        $currency    = $this->params->get('currency', 'BRL');
        $url         = $sandbox ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';

        $name  = $data['name'] ?? Text::_('PLG_CONTENT_PAYPALBUTTON_DEFAULT_PRODUCT');
        $price = $data['price'] ?? '0.00';
        $price = str_replace(',', '.', $price); // Ensure dot decimal separator

        $style = $this->params->get('button_style', 'pill');
        $color = $this->params->get('button_color', 'gold');
        $label = $this->params->get('button_label', 'checkout');

        // Design classes
        $btnClasses = "paypal-button-container paypal-style-{$style} paypal-color-{$color}";

        ob_start();
        ?>
<div class="<?php echo $btnClasses; ?>" style="margin: 1rem 0;">
    <form action="<?php echo $url; ?>" method="post" target="_top">
        <input type="hidden" name="cmd" value="_xclick">
        <input type="hidden" name="business" value="<?php echo htmlspecialchars($paypalEmail); ?>">
        <input type="hidden" name="item_name" value="<?php echo htmlspecialchars($name); ?>">
        <input type="hidden" name="amount" value="<?php echo htmlspecialchars($price); ?>">
        <input type="hidden" name="currency_code" value="<?php echo htmlspecialchars($currency); ?>">
        <input type="hidden" name="button_subtype" value="services">
        <input type="hidden" name="no_note" value="0">
        <input type="hidden" name="tax_rate" value="0.000">
        <input type="hidden" name="shipping" value="0.00">
        <input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynowCC_LG.gif:NonHostedGuest">

        <button type="submit" class="paypal-submit-btn">
            <span class="paypal-logo">
                <i>PayPal</i>
            </span>
            <span
                class="paypal-text"><?php echo Text::_('PLG_CONTENT_PAYPALBUTTON_LABEL_' . strtoupper($label)); ?></span>
        </button>
    </form>
    <style>
    .paypal-button-container {
        display: inline-block;
    }

    .paypal-submit-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 2rem;
        border: none;
        cursor: pointer;
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-weight: bold;
        transition: all 0.3s ease;
        min-width: 200px;
    }

    .paypal-submit-btn:hover {
        filter: brightness(0.9);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .paypal-style-pill .paypal-submit-btn {
        border-radius: 50px;
    }

    .paypal-style-rect .paypal-submit-btn {
        border-radius: 4px;
    }

    .paypal-color-gold .paypal-submit-btn {
        background-color: #ffc439;
        color: #111;
    }

    .paypal-color-blue .paypal-submit-btn {
        background-color: #0070ba;
        color: #fff;
    }

    .paypal-color-silver .paypal-submit-btn {
        background-color: #eee;
        color: #111;
    }

    .paypal-color-black .paypal-submit-btn {
        background-color: #2c2e2f;
        color: #fff;
    }

    .paypal-logo i {
        font-style: italic;
        font-weight: 800;
        margin-right: 8px;
        letter-spacing: -1px;
    }

    .paypal-text {
        font-size: 0.9rem;
        text-transform: capitalize;
    }
    </style>
</div>
<?php
            return ob_get_clean();
                }
        }