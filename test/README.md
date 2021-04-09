## PHP env

The dockerized env can be used to test php code.

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