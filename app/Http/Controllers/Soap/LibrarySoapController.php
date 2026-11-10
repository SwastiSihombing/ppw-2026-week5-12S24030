<?php

namespace App\Http\Controllers\Soap;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Layanan SOAP ringkas untuk Sistem Administrasi Yudisium (legacy).
 * Operasi CheckClearanceStatus mengecek status bebas pustaka berdasarkan NIM.
 */
class LibrarySoapController extends Controller
{
    private const TARGET_NAMESPACE = 'http://sipus.del.ac.id/soap/sipus';

    /**
     * GET /soap/sipus.wsdl
     * Menyajikan kontrak formal layanan.
     */
    public function wsdl(): Response
    {
        $namespace = self::TARGET_NAMESPACE;
        $endpointUrl = htmlspecialchars(url('/soap/sipus'), ENT_XML1, 'UTF-8');

        $wsdl = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<definitions name="SipusSoapService"
             targetNamespace="{$namespace}"
             xmlns:tns="{$namespace}"
             xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
             xmlns:xsd="http://www.w3.org/2001/XMLSchema"
             xmlns="http://schemas.xmlsoap.org/wsdl/">

    <!-- 1. Message: parameter input dan output -->
    <message name="CheckClearanceStatusRequest">
        <part name="nim_peminjam" type="xsd:string"/>
    </message>
    <message name="CheckClearanceStatusResponse">
        <part name="nim_peminjam" type="xsd:string"/>
        <part name="active_loans" type="xsd:int"/>
        <part name="is_cleared" type="xsd:boolean"/>
    </message>

    <!-- 2. PortType: daftar operasi yang disediakan -->
    <portType name="SipusPortType">
        <operation name="CheckClearanceStatus">
            <input message="tns:CheckClearanceStatusRequest"/>
            <output message="tns:CheckClearanceStatusResponse"/>
        </operation>
    </portType>

    <!-- 3. Binding: mengikat operasi ke SOAP over HTTP -->
    <binding name="SipusSoapBinding" type="tns:SipusPortType">
        <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
        <operation name="CheckClearanceStatus">
            <soap:operation soapAction="{$namespace}#CheckClearanceStatus"/>
            <input><soap:body use="literal" namespace="{$namespace}"/></input>
            <output><soap:body use="literal" namespace="{$namespace}"/></output>
        </operation>
    </binding>

    <!-- 4. Service: alamat endpoint -->
    <service name="SipusSoapService">
        <port name="SipusPort" binding="tns:SipusSoapBinding">
            <soap:address location="{$endpointUrl}"/>
        </port>
    </service>
</definitions>
XML;

        return $this->xml($wsdl);
    }

    /**
     * POST /soap/sipus
     * Memproses <soap:Envelope> berisi <nim_peminjam>.
     */
    public function handle(Request $request): Response
    {
        $rawXml = trim($request->getContent());

        if ($rawXml === '') {
            return $this->fault('soap:Client', 'Body XML SOAP tidak boleh kosong.');
        }

        // LIBXML_NONET: dokumen tidak boleh memuat sumber daya dari jaringan.
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($rawXml, options: LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            return $this->fault('soap:Client', 'Format XML tidak valid (malformed XML).');
        }

        if ($xml->getName() !== 'Envelope') {
            return $this->fault('soap:Client', 'Elemen akar pesan harus <soap:Envelope>.');
        }

        // Pencarian bebas prefix namespace agar klien lama tidak harus memakai prefix tertentu.
        $nimNodes = $xml->xpath('//*[local-name()="Body"]//*[local-name()="nim_peminjam"]');
        $nim = trim((string) ($nimNodes[0] ?? ''));

        if ($nim === '') {
            return $this->fault('soap:Client', 'Elemen <nim_peminjam> wajib diisi.');
        }

        $activeLoans = Peminjaman::where('nim_peminjam', $nim)
            ->where('status', 'dipinjam')
            ->count();

        $namespace = self::TARGET_NAMESPACE;
        $safeNim = htmlspecialchars($nim, ENT_XML1, 'UTF-8');
        $isCleared = $activeLoans === 0 ? 'true' : 'false';

        $response = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
               xmlns:m="{$namespace}">
    <soap:Header>
        <m:ProviderInfo>SIPUS-Del SOAP Engine v1.0</m:ProviderInfo>
    </soap:Header>
    <soap:Body>
        <m:CheckClearanceStatusResponse>
            <nim_peminjam>{$safeNim}</nim_peminjam>
            <active_loans>{$activeLoans}</active_loans>
            <is_cleared>{$isCleared}</is_cleared>
        </m:CheckClearanceStatusResponse>
    </soap:Body>
</soap:Envelope>
XML;

        return $this->xml($response);
    }

    /**
     * Pesan kesalahan standar SOAP 1.1 dengan status HTTP 500.
     */
    private function fault(string $faultCode, string $faultMessage): Response
    {
        $safeMessage = htmlspecialchars($faultMessage, ENT_XML1, 'UTF-8');

        $fault = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <soap:Fault>
            <faultcode>{$faultCode}</faultcode>
            <faultstring>{$safeMessage}</faultstring>
        </soap:Fault>
    </soap:Body>
</soap:Envelope>
XML;

        return $this->xml($fault, 500);
    }

    private function xml(string $content, int $status = 200): Response
    {
        return response($content, $status)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }
}