<?php
/**
 * Form pipeline self-check. From the site root:
 *   studio wp eval-file wp-content/themes/<theme>/bin/check-forms.php
 *
 * @package VanillaStarter
 */

namespace VanillaStarter;

$fail = 0;
$is   = function ( $got, $want, string $what ) use ( &$fail ): void {
	if ( $got !== $want ) {
		++$fail;
		echo "FAIL: $what\n  got:  " . var_export( $got, true ) . "\n  want: " . var_export( $want, true ) . "\n";
	}
};

$email = array( 'Email', 'email', 'Enter your email.' );
$is( validate_field( ' a@b.co ', $email ), array( 'a@b.co', '' ), 'email trimmed' );
$is( validate_field( 'nope', $email )[1] !== '', true, 'bad email refused' );
$is( validate_field( '', $email ), array( '', 'Enter your email.' ), 'missing email message' );
$is( validate_field( '', array( 'Name', 'text', '' ) ), array( '', '' ), 'optional field may be empty' );
$is( validate_field( array( 'x' ), array( 'Name', 'text', 'Need it.' ) ), array( '', 'Need it.' ), 'array value refused' );
$is( validate_field( str_repeat( 'a', 300 ), array( 'Name', 'text', '' ) )[0], str_repeat( 'a', 200 ), 'text capped at 200' );
$is( validate_field( 'javascript:alert(1)', array( 'Site', 'url', '' ) )[1] !== '', true, 'non-http url refused' );
$is( validate_field( 'https://example.com/x', array( 'Site', 'url', '' ) )[0], 'https://example.com/x', 'https url kept' );
$choice = array( 'Topic', 'choice', 'Pick one.', array( 'A', 'B' ) );
$is( validate_field( 'B', $choice )[0], 'B', 'choice kept' );
$is( validate_field( 'C', $choice ), array( '', 'Pick one.' ), 'unknown choice refused' );

$is( webhook_body( '', array( 'email' => 'a@b.co' ) ), '{"email":"a@b.co"}', 'empty template sends all fields' );
$body = webhook_body( '{"email": "{{email}}", "note": "{{ message }}", "id": 1}', array( 'email' => 'a@b.co', 'message' => "say \"hi\"\n" ) );
$is( json_decode( $body, true ), array( 'email' => 'a@b.co', 'note' => "say \"hi\"\n", 'id' => 1 ), 'template fills and escapes' );

foreach ( forms() as $key => $form ) {
	$is( isset( $form['fields']['email'] ), true, "$key has an email field" );
}

// Turnstile: widget goes before the error slot only on forms that have it on with both keys set.
$opt = fn( $v ) => fn() => $v;
add_filter( 'pre_option_vs_turnstile', $opt( array( 'site' => 'SITEKEY', 'secret' => 'S' ) ) );
add_filter( 'pre_option_vs_forms', $opt( array( 'contact' => array( 'turnstile' => true ) ) ) );
$form = fn( $k ) => '<form data-vs-form><input type="hidden" name="vs_form" value="' . $k . '"><p class="vs-form-err" role="alert" data-error></p></form>';
$out  = add_turnstile( $form( 'contact' ) . $form( 'newsletter' ) );
$is( substr_count( $out, 'cf-turnstile' ), 1, 'widget only on the form with Turnstile on' );
$is( str_contains( $out, '<div class="cf-turnstile" data-sitekey="SITEKEY"></div><p class="vs-form-err"' ), true, 'widget sits before the error slot' );
$is( turnstile_on( 'newsletter' ), false, 'unticked form is off' );
add_filter( 'pre_option_vs_turnstile', $opt( array( 'site' => 'SITEKEY', 'secret' => '' ) ), 11 );
$is( turnstile_on( 'contact' ), false, 'missing secret turns it off' );
$is( add_turnstile( $form( 'contact' ) ), $form( 'contact' ), 'no widget without keys' );
$is( turnstile_verify( '', '' ), false, 'empty token refused without a request' );

echo $fail ? "$fail failed\n" : "PASS\n";
