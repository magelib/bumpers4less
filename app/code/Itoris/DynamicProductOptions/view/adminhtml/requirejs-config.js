/**
* Copyright © 2016 ITORIS INC. All rights reserved.
* See license agreement for details
*/
var config = {
    map: {
        '*': {
            'itoris_options'  : 'Itoris_DynamicProductOptions/js/options',
            'itoris_spectrum'  : 'Itoris_DynamicProductOptions/js/spectrum'
        }
    }
    ,
    shim: {
        "Itoris_DynamicProductOptions/js/composite": ["prototype"],
        "Itoris_DynamicProductOptions/js/helper": ["prototype"]

    }

};