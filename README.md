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

# PHP env

The dockerized env can be used to test php code without having to rely on a Nethesis PBX.

### Final container

Build an image and run a container that **copies** the project *app* folder content inside the container *app* folder.
> The app folder is already backed by the webdevops/php-nginx docker to be the default www location

`docker build -t script .`
`docker run -d -p 80:80 script`

### Dev container

Build and run a container where the project *app* folder is mounted to the container *app* folder.
You can simply edit the project source code and issue a new HTTP request to see the changes (no build and run phases after every change).

`docker build -t script .`
`docker run --rm -v $(pwd)/app:/app -it -p 80:80 script`

---

The HTTP request to be sent:

`http://localhost/?to=%22sip:abc@xx.xx.xx.xx;ip:fs-conn-id=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=tls:host.publicname.com#012%22`


---

# Logs

To implement a Push Notification script for the Nethesis CTI, there are two relevant logs you can observe:

System:


````
/var/log
````

in there, the best way to see the log is using tail on messages: `tail -10f messages`


Asterisk:

````
/var/log/asterisk
````

FlexSIP:

```
/var/opt/belledonne-communications/log/flexisip
```


# Push Notification Script

There are two possible ways to notify our backend (CURL) to create a push notification:

1. **Using a FlexSIP module**
  FlexSIP loads PushNotification module that internally creates an HTTP request for a given URI: `/opt/belledonne-communications/share/push-proxy/index.php`
  We can't use this option because the FlexSIP module requires a `pn-tok` parameter to be passed during a SIP registration and our current SDK (PortSIP) doesn't support it out of the box (it's a 3000$ feature  request).

2. **Using an AGI** (✅)
  AGI is analogous to CGI in Apache. AGI provides an interface between the Asterisk dialplan and an external program that wants to manipulate a channel in the dialplan. In general, the interface is synchronous - actions taken on a channel from an AGI block and do not return until the action is completed.
  For our purposes the AGI is located here: `/var/lib/asterisk/agi-bin/youneed_app_wakeup.php`

