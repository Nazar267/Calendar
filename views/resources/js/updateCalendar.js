
// Automatic updating of data from Google calendar

var params = {
    module : 'Google',
    view : 'Sync'
}

app.request.post({data: params});


// Automatic updating of data from Outlook calendar

var params = {
    module : 'RedooOutlook',
    view : 'Sync'
}

app.request.post({data: params});
