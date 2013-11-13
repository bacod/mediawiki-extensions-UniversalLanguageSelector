Given(/^I am at random page$/) do
	visit RandomPage
end

Given(/^I am logged out$/) do
end

Given(/^I am logged in$/) do
	visit(LoginPage).login_with(ENV['MEDIAWIKI_USER'], ENV['MEDIAWIKI_PASSWORD'])
end

Given(/^I set "(.*?)" as the interface language$/) do |language|
	code = on(PanelPage).language_to_code(language)
	visit(PanelPage, :using_params => {:extra => "setlang=#{code}"})
	@original_content_font = on(PanelPage).content_font
	@original_interface_font = on(PanelPage).interface_font
end

Given(/^I temporarily use "(.*?)" as the interface language$/) do |language|
	code = on(PanelPage).language_to_code(language)
	visit(PanelPage, :using_params => {:extra => "uselang=#{code}"})
end

Then(/^my interface language is "(.*?)"$/) do |language|
	code = on(PanelPage).language_to_code(language)
	on(PanelPage).interface_element.attribute('lang').should == code
end
