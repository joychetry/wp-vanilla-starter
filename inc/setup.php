<?php
/**
 * Theme supports, menus, editor styles, pattern category.
 *
 * Block themes already get title-tag, post-thumbnails, responsive-embeds,
 * editor-styles, html5 and feed links from core, so they are not repeated here.
 *
 * @package VanillaStarter
 */

namespace VanillaStarter;

add_action( 'after_setup_theme', __NAMESPACE__ . '\\setup' );
add_action( 'init', __NAMESPACE__ . '\\register_pattern_category' );
add_action( 'admin_menu', __NAMESPACE__ . '\\add_settings_page' );

/**
 * Registers menus and editor styles, drops supports the theme does not use.
 */
function setup(): void {
	register_nav_menus(
		array(
			'primary' => __( 'Primary', 'vanilla-starter' ),
			'footer'  => __( 'Footer', 'vanilla-starter' ),
		)
	);

	// The editor gets the same files the front end inlines.
	$files = array( 'assets/css/global.css' );
	foreach ( block_stylesheets() as $file ) {
		$files[] = 'assets/css/blocks/' . $file;
	}
	add_editor_style( $files );

	// The theme ships its own patterns; core's set only adds noise to the inserter.
	remove_theme_support( 'core-block-patterns' );
}

/**
 * Registers the theme's pattern category.
 */
function register_pattern_category(): void {
	register_block_pattern_category(
		'vanilla-starter',
		array( 'label' => __( 'Vanilla Starter', 'vanilla-starter' ) )
	);
}

/**
 * Adds Appearance > Theme Settings, one place for every theme control.
 */
function add_settings_page(): void {
	add_theme_page(
		__( 'Theme Settings', 'vanilla-starter' ),
		__( 'Theme Settings', 'vanilla-starter' ),
		'edit_theme_options',
		'vanilla-starter-settings',
		__NAMESPACE__ . '\\render_settings_page'
	);
}

/**
 * Renders the Theme Settings page. Styling lives in theme.json and the Site
 * Editor, so this page links to those controls and shows menu locations.
 */
function render_settings_page(): void {
	$controls = array(
		'site-editor.php?p=/styles'   => __( 'Styles: colors, typography, spacing', 'vanilla-starter' ),
		'site-editor.php?p=/template' => __( 'Templates', 'vanilla-starter' ),
		'site-editor.php?p=/pattern&postType=wp_template_part&categoryId=all-parts' => __( 'Template parts: header and footer', 'vanilla-starter' ),
		'site-editor.php?p=/pattern'  => __( 'Patterns', 'vanilla-starter' ),
		'font-library.php'            => __( 'Fonts', 'vanilla-starter' ),
		'nav-menus.php'               => __( 'Menus', 'vanilla-starter' ),
		'nav-menus.php?action=locations' => __( 'Menu locations', 'vanilla-starter' ),
	);
	$assigned = get_nav_menu_locations();
	$theme    = wp_get_theme();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Theme Settings', 'vanilla-starter' ); ?></h1>
		<p><?php echo esc_html( $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) ); ?></p>

		<h2><?php esc_html_e( 'Design and layout', 'vanilla-starter' ); ?></h2>
		<ul>
			<?php foreach ( $controls as $url => $label ) : ?>
				<li><a href="<?php echo esc_url( admin_url( $url ) ); ?>"><?php echo esc_html( $label ); ?></a></li>
			<?php endforeach; ?>
		</ul>

		<h2><?php esc_html_e( 'Menu locations', 'vanilla-starter' ); ?></h2>
		<table class="widefat striped" style="max-width:40rem">
			<tbody>
			<?php foreach ( get_registered_nav_menus() as $location => $name ) : ?>
				<?php $menu = empty( $assigned[ $location ] ) ? false : wp_get_nav_menu_object( $assigned[ $location ] ); ?>
				<tr>
					<th scope="row"><?php echo esc_html( $name ); ?></th>
					<td><?php echo $menu ? esc_html( $menu->name ) : esc_html__( 'Not assigned', 'vanilla-starter' ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
