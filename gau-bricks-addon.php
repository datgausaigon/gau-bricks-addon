<?php
/**
 * Plugin Name: Gau Bricks Addon
 * Description: Optimize Bricks Builder (Migrated from Gau Bricks Child Theme).
 * Version:     1.0.0
 * Author:      🐻
 * Author URI:  https://github.com/datgausaigon/
 * Text Domain: gau-bricks-addon
 * License:     Copyright © datgau.com - All Rights Reserved.
 */

namespace Gau\BricksAddon;

defined('ABSPATH') || exit;

final class Plugin
{

	public const VERSION = '1.0.0';

	public const ENABLE_VN_THEME_FILTER = 'gau/bricks/builder/enable_vietnam_theme';
	public const ENABLE_PANEL_SCROLLBAR_FILTER = 'gau/bricks/builder/enable_panel_scrollbar';
	public const ENABLE_ELEMENT_TEXT_POPUP_FILTER = 'gau/bricks/builder/enable_element_text_popup';
	public const ENABLE_COLOR_PALETTE_POPUP_FILTER = 'gau/bricks/builder/enable_color_palette_popup';

	public const ENABLE_COMPACT_ELEMENTS_PANEL_FILTER = 'gau/bricks/builder/enable_compact_elements_panel';
	public const ENABLE_CUSTOM_STRUCTURE_ITEM_RENAMING_FILTER = 'gau/bricks/builder/enable_custom_structure_item_renaming';

	private static ?self $instance = null;

	public static function instance(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct()
	{
		// Use plugins_loaded to ensure Bricks is potentially available or at least WP is ready
		add_action('plugins_loaded', [$this, 'init']);
	}

	public function init(): void
	{
		add_action('wp_enqueue_scripts', [$this, 'public_enqueue_assets'], 1000);
		add_action('wp_enqueue_scripts', [$this, 'builder_enqueue_assets'], 1000);

		// Initialize modules
		$this->maybe_boot_modules();
	}

	public function maybe_boot_modules(): void
	{
		if ($this->is_element_text_popup_enabled()) {
			$path = self::includes_path() . 'element-text-popup.php';
			if (file_exists($path)) {
				require_once $path;
				Includes\Element_Text_Popup::instance();
			}
		}
	}

	public function public_enqueue_assets(): void
	{
		if (self::is_bricks_builder_main()) {
			return;
		}

		// Enqueue public styles if needed.
		// NOTE: Child theme style.css usually loaded automatically by theme.
		// For plugin, we explicitly enqueue if we have frontend styles.
		// The original 'gau-bricks__theme-style' was get_stylesheet_uri() (style.css).
		// We'll skip style.css for now unless there are actual styles in it,
		// or if we want to preserve that behavior, we'd need a frontend.css.
		// Currently style.css in theme seems empty/minimal based on previous read.
	}

	public function builder_enqueue_assets(): void
	{
		if (!self::is_bricks_builder()) {
			return;
		}

		wp_register_script(
			'gau-bricks-addon__builder-script',
			self::assets_url() . 'builder.js',
			[],
			self::VERSION,
			true
		);
		wp_enqueue_script('gau-bricks-addon__builder-script');

		wp_register_style(
			'gau-bricks-addon__builder-style',
			self::assets_url() . 'builder.css',
			[],
			self::VERSION,
			'all'
		);
		wp_enqueue_style('gau-bricks-addon__builder-style');

		if ($this->is_vietnam_theme_enabled()) {
			wp_register_style(
				'gau-bricks-addon__builder__vietnam-theme-style',
				self::assets_url() . 'vietnam-theme.css',
				[],
				self::VERSION,
				'all'
			);
			wp_enqueue_style('gau-bricks-addon__builder__vietnam-theme-style');
		}

		if ($this->is_panel_scrollbar_enabled()) {
			wp_register_style(
				'gau-bricks-addon__builder__panel-scrollbar-style',
				self::assets_url() . 'panel-scrollbar.css',
				[],
				self::VERSION,
				'all'
			);
			wp_enqueue_style('gau-bricks-addon__builder__panel-scrollbar-style');
		}

		if ($this->is_compact_elements_panel_enabled()) {
			wp_register_style(
				'gau-bricks-addon__builder__compact-elements-panel-style',
				self::assets_url() . 'compact-elements-panel.css',
				[],
				self::VERSION,
				'all'
			);
			wp_enqueue_style('gau-bricks-addon__builder__compact-elements-panel-style');
		}

		if ($this->is_element_text_popup_enabled()) {
			wp_register_style(
				'gau-bricks-addon__builder__element-text-popup-style',
				self::assets_url() . 'element-text-popup.css',
				[],
				self::VERSION,
				'all'
			);
			wp_enqueue_style('gau-bricks-addon__builder__element-text-popup-style');
		}

		if ($this->is_color_palette_popup_enabled()) {
			wp_register_style(
				'gau-bricks-addon__builder__color-palette-popup-style',
				self::assets_url() . 'color-palette-popup.css',
				[],
				self::VERSION,
				'all'
			);
			wp_enqueue_style('gau-bricks-addon__builder__color-palette-popup-style');

			wp_register_script(
				'gau-bricks-addon__builder__color-palette-popup-script',
				self::assets_url() . 'color-palette-popup.js',
				[],
				self::VERSION,
				true
			);
			wp_enqueue_script('gau-bricks-addon__builder__color-palette-popup-script');

			// Localize SVGs from Bricks parent theme
			// NOTE: We still use get_template_directory() to access Bricks assets since Bricks is the parent theme
			$bricks_dir = get_template_directory();
			$icon_expand = '';
			$icon_collapse = '';

			if (file_exists($bricks_dir . '/assets/svg/builder/expand.svg')) {
				$icon_expand = file_get_contents($bricks_dir . '/assets/svg/builder/expand.svg');
			}
			if (file_exists($bricks_dir . '/assets/svg/builder/collapse.svg')) {
				$icon_collapse = file_get_contents($bricks_dir . '/assets/svg/builder/collapse.svg');
			}

			wp_localize_script('gau-bricks-addon__builder__color-palette-popup-script', 'GauBricksColorPalette', [
				'icons' => [
					'expand' => $icon_expand,
					'collapse' => $icon_collapse,
				],
			]);
		}

		if ($this->is_custom_structure_item_renaming_enabled()) {
			wp_register_style(
				'gau-bricks-addon__builder__custom-structure-item-renaming-style',
				self::assets_url() . 'custom-structure-item-renaming.css',
				[],
				self::VERSION,
				'all'
			);
			wp_enqueue_style('gau-bricks-addon__builder__custom-structure-item-renaming-style');

			wp_register_script(
				'gau-bricks-addon__builder__custom-structure-item-renaming-script',
				self::assets_url() . 'custom-structure-item-renaming.js',
				[],
				self::VERSION,
				true
			);
			wp_enqueue_script('gau-bricks-addon__builder__custom-structure-item-renaming-script');
		}
	}

	/* ================= Paths & URIs ================= */

	public static function plugin_dir(): string
	{
		return plugin_dir_path(__FILE__);
	}

	public static function plugin_url(): string
	{
		return plugin_dir_url(__FILE__);
	}

	public static function includes_path(): string
	{
		return self::plugin_dir() . 'includes/';
	}

	public static function assets_path(): string
	{
		return self::plugin_dir() . 'assets/';
	}

	public static function assets_url(): string
	{
		return self::plugin_url() . 'assets/';
	}

	/* ================= Bricks checks ================= */

	public static function is_bricks_builder(): bool
	{
		return function_exists('bricks_is_builder') ? (bool) bricks_is_builder() : false;
	}

	public static function is_bricks_builder_main(): bool
	{
		return function_exists('bricks_is_builder_main') ? (bool) bricks_is_builder_main() : false;
	}

	/* ================= Flags ================= */

	private function is_vietnam_theme_enabled(): bool
	{
		return (bool) apply_filters(self::ENABLE_VN_THEME_FILTER, false);
	}

	private function is_panel_scrollbar_enabled(): bool
	{
		return (bool) apply_filters(self::ENABLE_PANEL_SCROLLBAR_FILTER, false);
	}

	private function is_compact_elements_panel_enabled(): bool
	{
		return (bool) apply_filters(self::ENABLE_COMPACT_ELEMENTS_PANEL_FILTER, true);
	}

	private function is_element_text_popup_enabled(): bool
	{
		return (bool) apply_filters(self::ENABLE_ELEMENT_TEXT_POPUP_FILTER, true);
	}

	private function is_color_palette_popup_enabled(): bool
	{
		return (bool) apply_filters(self::ENABLE_COLOR_PALETTE_POPUP_FILTER, true);
	}

	private function is_custom_structure_item_renaming_enabled(): bool
	{
		return (bool) apply_filters(self::ENABLE_CUSTOM_STRUCTURE_ITEM_RENAMING_FILTER, true);
	}
}

Plugin::instance();
