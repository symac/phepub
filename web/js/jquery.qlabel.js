/**
 * Copyright 2014 Google Inc. All rights reserved.
 * Author: Denny Vrandecic <vrandecic@google.com>
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
;(function ($, window, document, undefined) {

var language = null;
var className = 'qlabel';
var labels = {};
var loaders = {};
loaders.count = 0;

// private functions

var checkString = function(str) {
    if (!(typeof str == 'string' || str instanceof String)) {
        throw "parameter is not a string";
    }
};

var checkLanguage = function(lang) {
    checkString(lang);
    if (!(/^[a-z\-]+$/i.test(lang))) {
        throw "lang parameter is not a lang code";
    }
};

var setLanguage = function(lang) {
    checkLanguage(lang);
    language = lang;
};

var display = function() {
    $('.' + className + ', .qdescription').each(function(index, element) {
      var id = getURI(element);
      var contentType = "";
      if ($(element).hasClass("qdescription")) {
        contentType = 'description';
      }

      if (id !== undefined) {
          var label = getLabel(getURI(element), getLanguage(), contentType);
          if (contentType == "description") {
            console.log(" >>> " + label);
          }
          if (label !== undefined) { 
            $(element).text(label); 
          }
          else if (contentType == "description") {
            $(element).html("&mdash;");  
          }
      }
    });
};

var getURI = function(element) {
    var $this = $(element);
    var uri = undefined;
    $.each(['data-qlabel', 'its-ta-ident-ref', 'resource', 'about', 'href'], function(index, attribute) {
      if ($this.attr(attribute) !== undefined) {
          uri = $this.attr(attribute);
          return false;
      }
    });
    return uri;
};

var gatherURIs = function() {
    return $('.' + className + ', .qdescription').map(function() {
      return getURI(this);
    }).get();
};

var checkURI = checkString; // TODO, could be made a bit tighter

var normalizeURI = function(URI) {
    for (var i=loaders.count-1; i>=0; i--) {
        var result = loaders[i].tester(URI);
        if (result !== undefined) {
            return result;
        }
    }
    return URI;
};

var checkLabel = checkString;

var checkURIs = function(URIs) {
    if (!$.isArray(URIs)) {
        throw "URIs parameter is wrong"
    }
    $(URIs).each(function(index, URI) {
      checkURI(URI);
    });
};

var checkTester = $.isFunction;

var checkLoader = $.isFunction;

// loaders

// Wikidata loader

var wikidataTester = function(URI) {
    var pattern1 = /^Q\d+$/i;
    if (pattern1.test(URI)) return URI;
    var pattern2 = /^https?:\/\/(?:www\.)?wikidata\.org\/(?:wiki|entity)\/(Q\d+)$/i;
    var m = URI.match(pattern2);
    if (m === null) return undefined;
    return m[1];
};

var wikidataLoader = function(URIs) {
    // TODO deal with more than 50 entities
    return $.getJSON('https://www.wikidata.org/w/api.php?callback=?', {
      action: 'wbgetentities' ,
      ids: URIs.join('|'),
      props: 'labels|descriptions' ,
      format: 'json',
      languages : getLanguage(),
      languagefallback : ''
    }, function(data) {
      // TODO handle errors and exceptions
      $.each(data.entities, function(id, entity) {

        $.each(entity.labels, function(lang, label) {
          setLabel(id, lang, label.value);
        });
        $.each(entity.descriptions, function(lang, label) {
          //console.log(id + " - " + lang + " > " + label.value);
          setLabel(id, lang, label.value, "description");
        });
      });
    });
};

var switchLanguage = function(lang) {
    setLanguage(lang);
    return loadLabels(gatherURIs()).then(display).promise();
};

var getLanguage = function() {
    return language;
};

var setClassName = function(clsName) {
    className = clsName;
};

var getClassName = function() {
    return className;
};

var setLabel = function(URI, lang, label, typeLabel) {
    checkURI(URI);
    checkLanguage(lang);
    checkLabel(label);
    URI = normalizeURI(URI);
    if (!(URI in labels)) {
        labels[URI] = {};
    }
    if (!(lang in labels[URI])) {
        labels[URI][lang] = {};
    }

    if (typeLabel == "description") {
      labels[URI][lang]['description'] = label;
    } else {
      labels[URI][lang]['label'] = label;
    }
    
    return getLabel(URI, lang, typeLabel);
};

var getLabel = function(URI, lang, typeLabel) {
    checkURI(URI);
    checkLanguage(lang);
    URI = normalizeURI(URI);
    if (URI in labels) {
        if (lang in labels[URI]) {
          if (typeLabel == 'description') {

            return labels[URI][lang]['description'];
          } else {
            return labels[URI][lang]['label'];
          }
        }
    }
    return undefined;
};

var getLabels = function(URI) {
    checkURI(URI);
    URI = normalizeURI(URI);
    if (URI in labels) {
        return $.extend({}, labels[URI]);
    }
    return undefined;
};

// TODO this function can be cleaned up
var loadLabels = function(URIs) {
    if (URIs === [] || URIs === undefined || URIs === null) {
        URIs = gatherURIs();
    }
    checkURIs(URIs);
    // do not load already loaded URIs: URIs = URIs - knownuris
    URIs = $.map(URIs, function(uri, index) {
      var normalURI = normalizeURI(uri);
      if ($.inArray(normalURI, getKnownURIs()) !== -1) {
          return undefined;
      }
      return normalURI;
    });
    var promises = [];
    for (var i=loaders.count-1; i>=0; i--) {
        var rest = [];
        var loadableURIs = $.map(URIs, function(uri, index) {
          var normalURI = loaders[i].tester(uri);
          if (normalURI === undefined) rest.push(uri);
          return normalURI;
        });
        if (loadableURIs.length > 0) {
            promises.push(loaders[i].loader(loadableURIs));
        }
        URIs = rest;
    }
    return $.when.apply($, promises).promise();
};

var getKnownURIs = function() {
    return $.map(labels, function(value, key) {
      return key;
    });
};

var setLoader = function(tester, loader) {
    checkTester(tester);
    checkLoader(loader);
    loaders[loaders.count] = {
      tester: tester,
      loader: loader
    };
    return ++loaders.count;
};

$.qLabel = {
  switchLanguage: switchLanguage,
  getLanguage: getLanguage,
  setClassName: setClassName,
  getClassName: getClassName,
  setLabel: setLabel,
  getLabel: getLabel,
  getLabels: getLabels,
  loadLabels: loadLabels,
  getKnownURIs: getKnownURIs,
  setLoader: setLoader,
};

setLoader(wikidataTester, wikidataLoader);

// public interface documentation
// switchLanguage(lang) - set the language and switch the display
// getLanguage() - get the currently set language
// setClassName(clsName) - set the classname used to find the elements
// getClassName() - get the classname used to find the elements
// getLabel(URI, lang) - return the label of the URI in the given language
// getLabels(URI) - return all labels for a URI
// setLabel(URI, lang, label) - set a specific label in the given language
// loadLabels([callback, [URIs]]) - load the URIs, then execute callback. If no
//     URIs are given, load all URIs mentioned on the page in qlabel elements.
//     callback takes no parameters.
// getKnownURIs() - return all loaded URIs 
// setLoader(tester, loader) - set a loader for URIs that will be run when the
//     tester function hits. signature of the callback functions:
//     string tester(string URI) - takes a URI and returns a posibly normalized
//         URI to load, or undefined otherwise
//     promise loader([string] URIs) - takes an array of URIs and returns a
//         promise. The promise returns URIs to languages to labels.

})(jQuery, window, document);