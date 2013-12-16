/**
 * 
 * Mapbender social media connector is an interface to give a Mapbender map
 * context to social network community.
 */
var Mapbender = Mapbender || {};
Mapbender.SMC = Mapbender.SMC || {};

Mapbender.SMC.callEmail = function(subject, url){
    if(subject && url){
        var mail_cmd = "mailto:?subject=" + subject + "&body=" + encodeURIComponent(url);
        win = window.open(mail_cmd,'emailWindow');
        if (win && win.open &&!win.closed) win.close();
    }
};
Mapbender.SMC.callTwitter = function(title, url){
    if(title && url){
        var cmd = $('<a href="http://www.twitter.com/home?status=' + title + ': ' + encodeURIComponent(url) + '" target="_BLANK">Twitter</a>');
        cmd[0].click();
        cmd.remove();
        cmd = null;
    }
};
Mapbender.SMC.callFacebook = function(title, url){
    if(title && url){
        var cmd = $('<a href="http://www.facebook.com/sharer.php?u=' + encodeURIComponent(url) + '&t=' + title + '" target="_BLANK">Facebook</a>');
        cmd[0].click();
        cmd.remove();
        cmd = null;
    }
};
Mapbender.SMC.callGooglePlus = function(title, url){
    if(url){
        var cmd = $('<a href="http://plus.google.com/share?url=' + encodeURIComponent(url) + '" target="_BLANK">Google+</a>');
        cmd[0].click();
        cmd.remove();
        cmd = null;
    }
};