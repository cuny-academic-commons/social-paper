<?php
/**
 * Template for creating a new social paper from the front end.
 *
 * @package Social_Paper
 * @subpackage Template
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?><title>Create a new social paper</title><?php endif; ?>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<div id="page">
	<?php wp_editor( '', 'social-paper-editor' ) ?>
</div>

</body>

</html>