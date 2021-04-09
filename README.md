# Description

The relevant bits are defined inside the `youneed_app_wakeup.php` file.
This script will be called by Asterisk whenever an incoming call arrives.
It requires a configuration JSON located on a PBX at **/etc/asterisk/nethcti_push_configuration.json**.

JSON file format:

```json
{
    "NotificationServerURL": "https://pp.nethesis.it/NotificaPush",
    "Host": "1772-neth-01.youneed.tech",
    "SystemId": "1D8B2BBA-02E4-40AE-8FD6-5255CFAFF0F8",
    "Secret": "1f259905a2f54f56b572e4998881f2abf77e0af8931fff072afc4f8c549d4098"
}
```
---

## Configuring a FreePBX Module

1. Copy *youneed_app_wakeup.php* into *agi-bin* folder. (In this project it's already there)
1. Compress the whole module as tar.gz (Done automatically when a new github release is created)
1. Upload the archive in */usr/src/nethvoice/modules/* (on a Nethesis PBX)
1. Run `/etc/e-smith/events/actions/nethserver-nethvoice-conf`

---

## Logs

There are two relevant logs you can observe:

1. System:

  ````
  /var/log
  ````

in there, the best way to see the log is using tail on messages: `tail -10f messages`


2. Asterisk:

  ````
  /var/log/asterisk
  ````

3. FlexSIP:

  ```
  /var/opt/belledonne-communications/log/flexisip
  ```


# Push Notification Script

There are two possible ways to notify our backend (CURL) to create a push notification:

1. **Using a FlexSIP module**
  FlexSIP loads PushNotification module that internally creates an HTTP request for a given URI: `/opt/belledonne-communications/share/push-proxy/index.php`
  We can't use this option because the FlexSIP module requires a `pn-tok` parameter to be passed during a SIP registration and our current SDK (PortSIP) doesn't support it out of the box.

2. **Using an AGI** (✅)
  AGI is analogous to CGI in Apache. AGI provides an interface between the Asterisk dialplan and an external program that wants to manipulate a channel in the dialplan. In general, the interface is synchronous - actions taken on a channel from an AGI block and do not return until the action is completed.
  For our purposes the AGI is located here: `/var/lib/asterisk/agi-bin/youneed_app_wakeup.php`

  > If a script is uploaded without a FreePBX module, it should have permissions set to 775


