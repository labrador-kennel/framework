<?php

namespace Labrador\Http;

enum ContentType : string {
    case All = '*/*';
    case AtomXml = 'application/atom+xml';
    case Binary = 'application/octet-stream';
    case FormUrlEncoded = 'application/x-www-form-urlencoded';
    case Gif = 'image/gif';
    case GraphQl = 'application/graphql+json';
    case Html = 'text/html';
    case Jpeg = 'image/jpeg';
    case Json = 'application/json';
    case MultipartFormData = 'multipart/form-data';
    case Pdf = 'application/pdf';
    case Plain = 'text/plain';
    case Png = 'image/png';
    case Rss = 'application/rss+xml';
    case Xml = 'application/xml';
}