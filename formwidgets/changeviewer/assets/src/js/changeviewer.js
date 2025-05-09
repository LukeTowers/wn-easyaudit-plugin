import * as jsondiffpatch from 'jsondiffpatch';
import * as htmlFormatter from 'jsondiffpatch/formatters/html';

window.jsondiffpatch = jsondiffpatch.create({
    objectHash: (obj) => JSON.stringify(obj), // fallback for unkeyed objects
    arrays: {
        detectMove: true,
        includeValueOnMove: true
    },
    textDiff: {
        minLength: 1 // perform diff even on short strings
    }
});
window.jsondiffpatchHtml = htmlFormatter;
