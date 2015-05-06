/**
 * Simple event dispatcher
 *
 * @author Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 11.08.2014 by WhereGroup GmbH & Co. KG
 */

window.EventDispatcher = {
    _listeners: {},

    on: function(name,callback){
        if(!this._listeners[name]){
            this._listeners[name] = [];
        }
        this._listeners[name].push(callback);
        return this;
    },

    off: function(name,callback){
        if(!this._listeners[name]){
            return;
        }
        if(callback){
            var listeners = this._listeners[name];
            for(var i in listeners){
                if(callback == listeners[i]){
                    listeners.splice(i,1);
                    return;
                }
            }
        }else{
            delete this._listeners[name];
        }

        return this;
    },

    dispatch: function(name,data){
        if(!this._listeners[name]){
            return;
        }

        var listeners = this._listeners[name];
        for(var i in listeners){
            listeners[i](data);
        }
        return this;
    }
};

