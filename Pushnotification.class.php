<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules;
/*
* Class stub for BMO Module class
* In getActionbar change "modulename" to the display value for the page
* In getActionbar change extdisplay to align with whatever variable you use to decide if the page is in edit mode.
*
*/

class Pushnotification extends \FreePBX_Helpers implements \BMO
{

	// Note that the default Constructor comes from BMO/Self_Helper.
	// You may override it here if you wish. By default every BMO
	// object, when created, is handed the FreePBX Singleton object.

	// Do not use these functions to reference a function that may not
	// exist yet - for example, if you add 'testFunction', it may not
	// be visibile in here, as the PREVIOUS Class may already be loaded.
	//
	// Use install.php or uninstall.php instead, which guarantee a new
	// instance of this object.
	public function install()
	{
	}
	public function uninstall()
	{
	}

	// The following two stubs are planned for implementation in FreePBX 15.
	public function backup()
	{
	}
	public function restore($backup)
	{
	}

	// We want to do dialplan stuff.
	public static function myDialplanHooks()
	{
		return 900; //at the very last instance
	}

	public function doDialplanHook(&$ext, $engine, $priority)
	{	
		;
		$context='func-apply-sipheaders-amb';
		
		$ext->add($context, 's', '1', new \ext_noop('Applying SIP Headers to channel ${CHANNEL}'));
		$ext->add($context, 's','', new \ext_agi('/var/lib/asterisk/agi-bin/youneed_app_wakeup.php,${ARG1},${ARG2},${ARG3},${CHANNEL(pjsip,call-id)}'));
		$ext->add($context, 's','', new \ext_setvar('TECH', '${CUT(CHANNEL,/,1)}'));
		$ext->add($context, 's','', new \ext_setvar('SIPHEADERKEYS', '${HASHKEYS(SIPHEADERS)}'));
		$ext->add($context, 's','', new \ext_while('$["${SET(sipkey=${SHIFT(SIPHEADERKEYS)})}" != ""]'));
		$ext->add($context, 's','', new \ext_setvar('sipheader', '${HASH(SIPHEADERS,${sipkey})}'));
		$ext->add($context, 's','', new \ext_execif('$["${sipheader}" = "unset" & "${TECH}" = "SIP"]', 'SIPRemoveHeader', '${sipkey}:'));
		$ext->add($context, 's','', new \ext_execif('$["${sipheader}" = "unset" & "${TECH}" = "PJSIP"]', 'Set', 'PJSIP_HEADER(remove,${sipkey})='));
		$ext->add($context, 's','', new \ext_execif('$["${sipkey}" = "Alert-Info" & ${REGEX("^<[^>]*>" ${sipheader})} != 1 & ${REGEX("\;info=" ${sipheader})} != 1]', 'Set', 'sipheader=<http://127.0.0.1>\;info=${sipheader}'));
		$ext->add($context, 's','', new \ext_execif('$["${sipkey}" = "Alert-Info" & ${REGEX("^<[^>]*>" ${sipheader})} != 1]', 'Set', 'sipheader=<http://127.0.0.1>${sipheader}'));
		$ext->add($context, 's','', new \ext_execif('$["${TECH}" = "SIP" & "${sipheader}" != "unset" ]', 'SIPAddHeader', '${sipkey}:${sipheader}'));
		$ext->add($context, 's','', new \ext_execif('$["${TECH}" = "PJSIP" & "${sipheader}" != "unset"]', 'Set', 'PJSIP_HEADER(add,${sipkey})=${sipheader}'));
		$ext->add($context, 's','', new \ext_endwhile());
		$ext->add($context, 's','', new \ext_return());
		
		$ext->replace('macro-dial-one', 's','dial', new \ext_dial('${DSTRING},${ARG1},${D_OPTIONS}b(func-apply-sipheaders-amb^s^1(${AMPUSER},${AMPUSERCIDNAME},${EXTTOCALL}))',''));
		$ext->replace('macro-dial', 's','nddialapp', new \ext_dial('${ds}b(func-apply-sipheaders-amb^s^1(${EXTTOCALL}))',''));
		$ext->replace('macro-dial', 's','hsdialapp', new \ext_dial('${${HuntMember}}${ds}b(func-apply-sipheaders-amb^s^1(${EXTTOCALL}))',''));
	}
}