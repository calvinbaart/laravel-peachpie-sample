<?php

const DNS_A = 0x00000001;
const DNS_NS = 0x00000002;
const DNS_CNAME = 0x00000010;
const DNS_SOA = 0x00000020;
const DNS_PTR = 0x00000800;
const DNS_HINFO = 0x00001000;
const DNS_CAA = 0x00002000;
const DNS_MX = 0x00004000;
const DNS_TXT = 0x00008000;
const DNS_A6 = 0x01000000;
const DNS_SRV = 0x02000000;
const DNS_NAPTR = 0x04000000;
const DNS_AAAA = 0x08000000;
const DNS_ANY = 0x10000000;
const DNS_ALL = DNS_A|DNS_NS|DNS_CNAME|DNS_SOA|DNS_PTR|DNS_HINFO|DNS_CAA|DNS_MX|DNS_TXT|DNS_A6|DNS_SRV|DNS_NAPTR|DNS_AAAA;

function dns_get_record(string $hostname, int $type = DNS_ANY, array &$authns, array &$addtl, bool $raw = false) : array
{
    // unimplemented
    return [];
}