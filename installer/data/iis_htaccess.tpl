<!-- <contrexx> -->
<!--     <core_routing> -->
             <configuration>
                 <system.webServer>
                    <rewrite>
                        <rules>
                            <!-- Deny direct access to directories containing sensitive data -->
                            <rule name="Protect sensitive data" stopProcessing="true">
                                <match url="." ignoreCase="false" />
                                <conditions logicalGrouping="MatchAll">
                                    <add input="{REQUEST_URI}" pattern="^/(config|tmp|websites|core/.*/Data|core_modules/.*/Data|modules/.*/Data)/" ignoreCase="false" />
                                </conditions>
                                <action type="CustomResponse" statusCode="403" statusReason="Forbidden" statusDescription="Forbidden" />
                            </rule>

                            <!-- Resolve language specific sitemap.xml -->
                            <rule name="Map multilingual sitemap.xml" stopProcessing="true">
                                <match url="^([a-z]{1,2}(?:-[A-Za-z]{2,4})?)\/sitemap.xml$" />
                                <action type="Rewrite" url="sitemap_{R:1}.xml" />
                            </rule>

                            <!-- Allow directory index files -->
                            <rule name="Map to index.php of directories" stopProcessing="true">
                                <match url="." ignoreCase="false" />
                                <conditions>
                                    <add input="{REQUEST_FILENAME}/index.php" matchType="IsFile" ignoreCase="false" />
                                </conditions>
                                <action type="Rewrite" url="{URL}/index.php" appendQueryString="true" />
                            </rule>

                            <!-- Redirect all requests to non-existing files to Contrexx -->
                            <rule name="Capture all" stopProcessing="true">
                                <match url=".?" ignoreCase="false" />
                                <conditions>
                                <add input="{REQUEST_FILENAME}" matchType="IsFile" ignoreCase="false" negate="true" />
                                </conditions>
                                <action type="Rewrite" url="index.php?__cap={URL}" appendQueryString="true" />
                            </rule>

                            <!-- Add captured request to index files -->
                            <rule name="Capture all #2" stopProcessing="true">
                                <match url="^(.*)index.php" ignoreCase="false" />
                                <action type="Rewrite" url="{R:1}index.php?__cap={URL}" appendQueryString="true" />
                            </rule>

                        </rules>
                    </rewrite>
                 </system.webServer>
             </configuration>
<!--     </core_routing> -->
<!-- </contrexx> -->
