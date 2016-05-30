package com.kijam.consultadecedulavenezolana;

import android.content.Context;
import android.support.v7.app.AppCompatActivity;
import android.os.Bundle;
import android.view.View;
import android.view.inputmethod.InputMethodManager;
import android.webkit.WebSettings;
import android.webkit.WebSettings.PluginState;
import android.webkit.WebView;
import android.widget.Button;
import android.widget.EditText;

public class ConsultarCedula extends AppCompatActivity {
    private WebView result;
    private EditText cedula;
    private String url_server = "https://<domain>/<app>.php";
    private View.OnClickListener searchButton = new View.OnClickListener() {
        public void onClick(View v) {
            result.loadUrl(url_server+"?cedula=" + cedula.getText());
            InputMethodManager inputMethodManager = (InputMethodManager)getSystemService(Context.INPUT_METHOD_SERVICE);
            inputMethodManager.hideSoftInputFromWindow(cedula.getWindowToken(), 0);
        }
    };
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_consultar_cedula);
        result = (WebView) this.findViewById(R.id.result);
        cedula = (EditText) this.findViewById(R.id.cedula);
        result.getSettings().setJavaScriptEnabled(true);
        result.setBackgroundColor(0x00000000);
        result.loadUrl(url_server);
        Button search = (Button) findViewById(R.id.buscar);
        search.setOnClickListener(searchButton);
    }
}
