<div class="xd-dashboard-panel xd-dashboard-panel--{$GridSize.LowerCase}">
    <header class="xd-dashboard-panel__header">
        <h3 class="xd-dashboard-panel__title">$Title</h3>
    </header>
    <div class="xd-dashboard-panel__main">
        <% if $ReportData %>
            <table class="table table-sm">
                <thead>
                    <tr>
                        <% loop $Columns %>
                            <th scope="col">$Title</th>
                        <% end_loop %>
                    </tr>
                </thead>
                <tbody>
                    <% loop $ReportData %>
                        <tr<% if $Link %> data-link="$Link"<% end_if %>>
                            <% loop $Columns %>
                                <td $Attributes>$Value.RAW</td>
                            <% end_loop %>
                        </tr>
                    <% end_loop %>
                </tbody>
            </table>
        <% else %>
            <p><%t XD\Dashboard\Model\Panel.NoDataForReport "No data for {report}" report=$Report.Title %></p>
            <p><a href="$Report.Link"><%t XD\Dashboard\Model\Panel.ViewFullReport "View full report" %></a></p>
        <% end_if %>
        
    </div>
    <footer class="xd-dashboard-panel__footer">
        <a href="$Report.Link"><%t XD\Dashboard\Model\Panel.FullReport "Full report" %></a>
    </footer>
</div>