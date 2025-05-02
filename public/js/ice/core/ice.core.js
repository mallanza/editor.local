// ice.core.js - Phase A
window.ICE = window.ICE || {};
ICE.cidCounter = 1;
ICE.currentUser = { id: null, name: null, ctsClass: null };

ICE.nextCid = function() {
    return ICE.cidCounter++;
};

ICE.setUser = function(user) {
    ICE.currentUser = {
        id: user.id,
        name: user.name,
        ctsClass: user.ctsClass
    };
};