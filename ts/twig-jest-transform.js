const path = require('path');
const twigLoader = require('twig-loader');
const twig = require('twig');

module.exports = {
    process(src, filename, config, options) {
        return ';';
//      return twigLoader(src);
    },
};

