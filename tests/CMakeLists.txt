add_subdirectory( controllers )
add_subdirectory( models )

add_midas_style_test( StyleTestsRodentControllers ${CMAKE_SOURCE_DIR}/modules/rodent/tests/controllers )
add_midas_style_test( StyleTestsRodentModels ${CMAKE_SOURCE_DIR}/modules/rodent/tests/models )