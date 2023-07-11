In order to compile:
1) Install Python 2.7
2) If you will reveive error with python path, try this:
    add to npm-config (path depends on your system)
    `npm config set python "C:\Python27\python.exe"`
3) npm install -g typescript
4) npm install

In case of gyp errors, try this (re-install sass):
1. `npm uninstall node-sass`
2. `npm i sass --save`

In case of this error: '.' is not recognized as an internal or external command:
Use direct function `./node_modules/.bin/encore production`
